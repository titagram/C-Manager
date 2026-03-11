<?php

namespace App\Support;

use App\Enums\StatoLottoProduzione;
use App\Enums\StatoOrdine;
use App\Enums\StatoPreventivo;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;

class NextActionAdvisor
{
    /**
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    public function forPreventivo(Preventivo $preventivo): array
    {
        $preventivo->loadMissing('ordine');

        return match ($preventivo->stato) {
            StatoPreventivo::BOZZA => $this->build(
                title: 'Completa il preventivo',
                message: 'Verifica righe e totali, poi porta il preventivo in stato Accettato.',
                ctaLabel: 'Apri modifica preventivo',
                ctaUrl: route('preventivi.edit', $preventivo),
                level: 'warning'
            ),
            StatoPreventivo::INVIATO => $this->build(
                title: 'Registra l\'esito cliente',
                message: 'Quando ricevi conferma, aggiorna lo stato a Accettato per sbloccare il flusso ordine.',
                ctaLabel: 'Apri preventivo',
                ctaUrl: route('preventivi.show', $preventivo),
                level: 'warning'
            ),
            StatoPreventivo::ACCETTATO => $preventivo->ordine
                ? $this->build(
                    title: 'Prosegui con l\'ordine',
                    message: "Il preventivo è già stato convertito in ordine ({$preventivo->ordine->numero}).",
                    ctaLabel: 'Apri ordine',
                    ctaUrl: route('ordini.show', $preventivo->ordine),
                    level: 'success'
                )
                : $this->build(
                    title: 'Crea l\'ordine da questo preventivo',
                    message: 'Il preventivo è accettato: converti in ordine per avviare pianificazione e produzione.',
                    ctaLabel: 'Apri preventivo in modifica',
                    ctaUrl: route('preventivi.edit', $preventivo),
                    level: 'success'
                ),
            StatoPreventivo::RIFIUTATO => $this->build(
                title: 'Flusso chiuso sul preventivo',
                message: 'Preventivo rifiutato: nessuna azione produttiva richiesta.',
                ctaLabel: null,
                ctaUrl: null,
                level: 'muted'
            ),
            StatoPreventivo::SCADUTO => $this->build(
                title: 'Preventivo scaduto',
                message: 'Valuta riapertura o nuova emissione prima di proseguire con ordine/produzione.',
                ctaLabel: 'Apri modifica preventivo',
                ctaUrl: route('preventivi.edit', $preventivo),
                level: 'warning'
            ),
        };
    }

    /**
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    public function forOrdine(Ordine $ordine): array
    {
        $ordine->loadMissing('lottiProduzione');
        $lotti = $ordine->lottiProduzione;

        return match ($ordine->stato) {
            StatoOrdine::CONFERMATO => $this->adviceForOrdineConfermato($ordine, $lotti),
            StatoOrdine::IN_PRODUZIONE => $this->adviceForOrdineInProduzione($ordine, $lotti),
            StatoOrdine::PRONTO => $this->build(
                title: 'Ordine pronto per chiusura logistica',
                message: 'Procedi con consegna al cliente e avanzamento stato ordine.',
                ctaLabel: 'Apri modifica ordine',
                ctaUrl: route('ordini.edit', $ordine),
                level: 'success'
            ),
            StatoOrdine::CONSEGNATO => $this->build(
                title: 'Consegna registrata',
                message: 'Aggiorna a Fatturato quando la fase amministrativa è conclusa.',
                ctaLabel: 'Apri modifica ordine',
                ctaUrl: route('ordini.edit', $ordine),
                level: 'info'
            ),
            StatoOrdine::FATTURATO => $this->build(
                title: 'Ordine chiuso',
                message: 'Nessuna azione operativa residua su questo ordine.',
                ctaLabel: null,
                ctaUrl: null,
                level: 'muted'
            ),
            StatoOrdine::ANNULLATO => $this->build(
                title: 'Ordine annullato',
                message: 'Flusso operativo interrotto: nessuna azione consigliata.',
                ctaLabel: null,
                ctaUrl: null,
                level: 'muted'
            ),
        };
    }

    /**
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    public function forLotto(LottoProduzione $lotto): array
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return match ($lotto->stato) {
            StatoLottoProduzione::BOZZA => $isAdmin
                ? $this->build(
                    title: 'Prepara il lotto all\'avvio',
                    message: 'Calcola materiali, verifica piano di taglio e porta il lotto in stato operativo.',
                    ctaLabel: 'Apri modifica lotto',
                    ctaUrl: route('lotti.edit', $lotto),
                    level: 'warning'
                )
                : $this->build(
                    title: 'Lotto in attesa di configurazione',
                    message: 'Questo lotto è ancora in bozza e richiede la preparazione tecnica da parte di un amministratore.',
                    ctaLabel: null,
                    ctaUrl: null,
                    level: 'warning'
                ),
            StatoLottoProduzione::CONFERMATO => $this->build(
                title: 'Avvia la produzione',
                message: 'Il lotto è confermato: avvia la lavorazione per generare avanzamento operativo.',
                ctaLabel: 'Vai alla tabella lotti',
                ctaUrl: route('lotti.index'),
                level: 'info'
            ),
            StatoLottoProduzione::IN_LAVORAZIONE => $this->build(
                title: 'Completa il lotto',
                message: 'Completa il lotto per registrare consumi reali, scarichi magazzino e scarti.',
                ctaLabel: 'Vai alla tabella lotti',
                ctaUrl: route('lotti.index'),
                level: 'success'
            ),
            StatoLottoProduzione::COMPLETATO => $this->build(
                title: 'Verifica consuntivi',
                message: 'Controlla magazzino aggregato, scarti e tracciabilità FITOK del lotto completato.',
                ctaLabel: 'Apri magazzino aggregato',
                ctaUrl: route('magazzino.aggregato'),
                level: 'muted'
            ),
            StatoLottoProduzione::ANNULLATO => $this->build(
                title: 'Lotto annullato',
                message: 'Nessuna azione operativa residua su questo lotto.',
                ctaLabel: null,
                ctaUrl: null,
                level: 'muted'
            ),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, LottoProduzione>  $lotti
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    private function adviceForOrdineConfermato(Ordine $ordine, $lotti): array
    {
        if ($lotti->isEmpty()) {
            return $this->build(
                title: 'Pianifica il primo lotto di produzione',
                message: 'L\'ordine è confermato ma non ha ancora lotti collegati.',
                ctaLabel: 'Crea nuovo lotto',
                ctaUrl: route('lotti.create', ['ordine_id' => $ordine->id, 'from' => 'ordine']),
                level: 'warning'
            );
        }

        $lottoDaAvviare = $lotti->first(fn (LottoProduzione $lotto) => in_array(
            $lotto->stato,
            [StatoLottoProduzione::BOZZA, StatoLottoProduzione::CONFERMATO],
            true
        ));

        if ($lottoDaAvviare) {
            return $this->build(
                title: 'Avvia il lotto pianificato',
                message: "Il lotto {$lottoDaAvviare->codice_lotto} è pronto per passare in lavorazione.",
                ctaLabel: 'Apri lotto',
                ctaUrl: route('lotti.show', $lottoDaAvviare),
                level: 'info'
            );
        }

        return $this->build(
            title: 'Ordine in attesa di avanzamento',
            message: 'Sono presenti lotti collegati: verifica stati e allineamento produzione.',
            ctaLabel: 'Apri lista lotti',
            ctaUrl: route('lotti.index'),
            level: 'info'
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, LottoProduzione>  $lotti
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    private function adviceForOrdineInProduzione(Ordine $ordine, $lotti): array
    {
        $lottoInLavorazione = $lotti->first(fn (LottoProduzione $lotto) => $lotto->stato === StatoLottoProduzione::IN_LAVORAZIONE);

        if ($lottoInLavorazione) {
            return $this->build(
                title: 'Completa il lotto in lavorazione',
                message: "Chiudi il lotto {$lottoInLavorazione->codice_lotto} per consolidare magazzino e scarti.",
                ctaLabel: 'Apri lotto',
                ctaUrl: route('lotti.show', $lottoInLavorazione),
                level: 'success'
            );
        }

        if ($lotti->isEmpty()) {
            return $this->build(
                title: 'Ordine senza lotti attivi',
                message: 'Lo stato è In Produzione ma non risultano lotti: crea o collega un lotto operativo.',
                ctaLabel: 'Crea nuovo lotto',
                ctaUrl: route('lotti.create', ['ordine_id' => $ordine->id, 'from' => 'ordine']),
                level: 'warning'
            );
        }

        return $this->build(
            title: 'Produzione da monitorare',
            message: 'Verifica i lotti collegati e porta l\'ordine verso lo stato Pronto.',
            ctaLabel: 'Apri lista lotti',
            ctaUrl: route('lotti.index'),
            level: 'info'
        );
    }

    /**
     * @return array{title:string,message:string,cta_label:?string,cta_url:?string,level:string}
     */
    private function build(
        string $title,
        string $message,
        ?string $ctaLabel,
        ?string $ctaUrl,
        string $level
    ): array {
        return [
            'title' => $title,
            'message' => $message,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
            'level' => $level,
        ];
    }
}
