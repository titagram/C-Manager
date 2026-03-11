<?php

namespace App\Support;

use App\Models\Bom;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Scarto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OperationalTimeline
{
    /**
     * @return Collection<int, array{at:\Illuminate\Support\Carbon,at_label:string,title:string,description:?string,tone:string}>
     */
    public function forOrdine(Ordine $ordine, int $limit = 30): Collection
    {
        $ordine->loadMissing(['preventivo', 'lottiProduzione']);
        $lotti = $ordine->lottiProduzione;
        $lottoIds = $lotti->pluck('id')->all();

        $events = collect([
            $this->event($ordine->created_at, 'Ordine creato', "Numero {$ordine->numero}", 'indigo'),
            $this->event($ordine->updated_at, "Stato ordine: {$ordine->stato->label()}", null, 'slate'),
            $this->event($ordine->data_consegna_prevista?->copy()->startOfDay(), 'Consegna prevista', null, 'amber'),
            $this->event($ordine->data_consegna_effettiva?->copy()->startOfDay(), 'Consegna effettiva registrata', null, 'green'),
        ]);

        if ($ordine->preventivo) {
            $events->push(
                $this->event(
                    $ordine->preventivo->updated_at ?? $ordine->preventivo->created_at,
                    "Origine da preventivo {$ordine->preventivo->numero}",
                    null,
                    'indigo'
                )
            );
        }

        foreach ($lotti as $lotto) {
            $events->push(
                $this->event(
                    $lotto->created_at,
                    "Lotto collegato: {$lotto->codice_lotto}",
                    "Stato {$lotto->stato->label()}",
                    'violet'
                )
            );
            $events->push($this->event($lotto->avviato_at ?? $lotto->data_inizio?->copy()->startOfDay(), "Avvio lotto {$lotto->codice_lotto}", null, 'violet'));
            $events->push($this->event($lotto->completato_at ?? $lotto->data_fine?->copy()->startOfDay(), "Completamento lotto {$lotto->codice_lotto}", null, 'green'));
        }

        $boms = Bom::query()
            ->where('ordine_id', $ordine->id)
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->get();

        foreach ($boms as $bom) {
            $events->push(
                $this->event(
                    $bom->generated_at ?? $bom->created_at,
                    "BOM generata: {$bom->codice}",
                    $bom->nome,
                    'indigo'
                )
            );
        }

        if ($lottoIds !== []) {
            $movimenti = MovimentoMagazzino::query()
                ->with('lottoMateriale')
                ->whereIn('lotto_produzione_id', $lottoIds)
                ->orderByDesc('data_movimento')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            foreach ($movimenti as $movimento) {
                $events->push(
                    $this->event(
                        $movimento->data_movimento ?? $movimento->created_at,
                        "Movimento magazzino: {$movimento->tipo->label()}",
                        $this->buildMovimentoDescription($movimento),
                        'amber'
                    )
                );
            }

            $scarti = Scarto::query()
                ->whereIn('lotto_produzione_id', $lottoIds)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            $this->appendScartoEvents($events, $scarti);
        }

        return $this->normalize($events, $limit);
    }

    /**
     * @return Collection<int, array{at:\Illuminate\Support\Carbon,at_label:string,title:string,description:?string,tone:string}>
     */
    public function forLotto(LottoProduzione $lotto, int $limit = 30): Collection
    {
        $lotto->loadMissing([
            'preventivo',
            'ordine',
            'consumiMateriale.lottoMateriale',
            'movimenti.lottoMateriale',
            'scarti',
        ]);

        $events = collect([
            $this->event($lotto->created_at, 'Lotto creato', $lotto->codice_lotto, 'violet'),
            $this->event($lotto->updated_at, "Stato lotto: {$lotto->stato->label()}", null, 'slate'),
            $this->event($lotto->avviato_at ?? $lotto->data_inizio?->copy()->startOfDay(), 'Lotto avviato', null, 'violet'),
            $this->event($lotto->completato_at ?? $lotto->data_fine?->copy()->startOfDay(), 'Lotto completato', null, 'green'),
        ]);

        if ($lotto->preventivo) {
            $events->push(
                $this->event(
                    $lotto->preventivo->updated_at ?? $lotto->preventivo->created_at,
                    "Origine da preventivo {$lotto->preventivo->numero}",
                    null,
                    'indigo'
                )
            );
        } elseif ($lotto->ordine) {
            $events->push(
                $this->event(
                    $lotto->ordine->updated_at ?? $lotto->ordine->created_at,
                    "Origine da ordine {$lotto->ordine->numero}",
                    null,
                    'indigo'
                )
            );
        }

        $boms = Bom::query()
            ->where('lotto_produzione_id', $lotto->id)
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->get();

        foreach ($boms as $bom) {
            $events->push(
                $this->event(
                    $bom->generated_at ?? $bom->created_at,
                    "BOM operativa: {$bom->codice}",
                    $bom->nome,
                    'indigo'
                )
            );
        }

        foreach ($lotto->consumiMateriale as $consumo) {
            $materialeCode = $consumo->lottoMateriale?->codice_lotto ?? 'n/d';
            $quantita = number_format((float) $consumo->quantita, 4, ',', '.');
            $baseDescription = "Lotto materiale {$materialeCode} · {$quantita}";

            $events->push($this->event($consumo->created_at, 'Fabbisogno materiale pianificato', $baseDescription, 'slate'));
            $events->push($this->event($consumo->opzionato_at, 'Materiale opzionato', $baseDescription, 'amber'));
            $events->push($this->event($consumo->consumato_at, 'Materiale consumato', $baseDescription, 'green'));
            $events->push($this->event($consumo->released_at, 'Opzione materiale rilasciata', $baseDescription, 'rose'));
        }

        foreach ($lotto->movimenti as $movimento) {
            $events->push(
                $this->event(
                    $movimento->data_movimento ?? $movimento->created_at,
                    "Movimento magazzino: {$movimento->tipo->label()}",
                    $this->buildMovimentoDescription($movimento),
                    'amber'
                )
            );
        }

        $this->appendScartoEvents($events, $lotto->scarti);

        return $this->normalize($events, $limit);
    }

    /**
     * @return array{at:\Illuminate\Support\Carbon,title:string,description:?string,tone:string}|null
     */
    private function event(mixed $at, string $title, ?string $description = null, string $tone = 'slate'): ?array
    {
        if (! $at) {
            return null;
        }

        $timestamp = $at instanceof Carbon ? $at : Carbon::parse($at);

        return [
            'at' => $timestamp,
            'title' => $title,
            'description' => $description,
            'tone' => $tone,
        ];
    }

    /**
     * @param  Collection<int, array{at:\Illuminate\Support\Carbon,title:string,description:?string,tone:string}|null>  $events
     * @return Collection<int, array{at:\Illuminate\Support\Carbon,at_label:string,title:string,description:?string,tone:string}>
     */
    private function normalize(Collection $events, int $limit): Collection
    {
        return $events
            ->filter()
            ->sortByDesc(fn (array $event) => $event['at']->getTimestamp())
            ->take($limit)
            ->values()
            ->map(function (array $event) {
                $event['at_label'] = $event['at']->format('d/m/Y H:i');

                return $event;
            });
    }

    private function buildMovimentoDescription(MovimentoMagazzino $movimento): string
    {
        $sign = $movimento->tipo->isPositive() ? '+' : '-';
        $quantita = number_format((float) $movimento->quantita, 4, ',', '.');
        $chunks = [
            "Quantità {$sign}{$quantita}",
        ];

        if ($movimento->lottoMateriale?->codice_lotto) {
            $chunks[] = "Lotto materiale {$movimento->lottoMateriale->codice_lotto}";
        }

        if ($movimento->causale) {
            $chunks[] = $movimento->causale;
        }

        return implode(' · ', $chunks);
    }

    private function buildScartoDescription(Scarto $scarto): string
    {
        $volume = number_format($scarto->calculatedVolumeMc(), 4, ',', '.');
        $dimensioni = $this->buildScartoDimensionLabel($scarto);

        return "Volume {$volume} m³ · {$dimensioni}";
    }

    /**
     * @param  Collection<int, Scarto>  $scarti
     */
    private function appendScartoEvents(Collection $events, Collection $scarti): void
    {
        $scarti
            ->groupBy(fn (Scarto $scarto) => $this->buildScartoAggregationKey($scarto))
            ->each(function (Collection $group) use ($events): void {
                /** @var Scarto|null $first */
                $first = $group->first();

                if (! $first instanceof Scarto) {
                    return;
                }

                $count = $group->count();
                $title = $count > 1 ? "Scarti registrati ({$count})" : 'Scarto registrato';
                $description = $count > 1
                    ? $this->buildGroupedScartoDescription($first, $group)
                    : $this->buildScartoDescription($first);

                $events->push(
                    $this->event(
                        $first->created_at,
                        $title,
                        $description,
                        'rose'
                    )
                );
            });
    }

    private function buildScartoAggregationKey(Scarto $scarto): string
    {
        return implode('|', [
            (string) optional($scarto->created_at)->format('Y-m-d H:i:s'),
            (string) ($scarto->lotto_produzione_id ?? ''),
            (string) ($scarto->lotto_materiale_id ?? ''),
            (string) ((int) $scarto->riutilizzabile),
            (string) ((int) $scarto->riutilizzato),
            (string) round((float) $scarto->lunghezza_mm),
            (string) round((float) $scarto->larghezza_mm),
            (string) round((float) $scarto->spessore_mm),
        ]);
    }

    /**
     * @param  Collection<int, Scarto>  $group
     */
    private function buildGroupedScartoDescription(Scarto $sample, Collection $group): string
    {
        $totaleVolume = number_format((float) $group->sum(fn (Scarto $scarto) => $scarto->calculatedVolumeMc()), 4, ',', '.');
        $dimensioni = $this->buildScartoDimensionLabel($sample);

        return "Pezzi {$group->count()} · Volume totale {$totaleVolume} m³ · {$dimensioni}";
    }

    private function buildScartoDimensionLabel(Scarto $scarto): string
    {
        return sprintf(
            '%s x %s x %s mm',
            number_format((float) $scarto->lunghezza_mm, 0, ',', '.'),
            number_format((float) $scarto->larghezza_mm, 0, ',', '.'),
            number_format((float) $scarto->spessore_mm, 0, ',', '.')
        );
    }
}
