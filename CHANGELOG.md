# Changelog

## [Unreleased] (2.0.0)

Rescriere completă, ca furnizor pentru `webrteu/invoicing` 2.x. **Incompatibil cu 1.x.**

### Adăugat
- `SmartBillProvider`: facturi (`/invoice`) și proforme (`/estimate`), PDF SmartBill, anulare, restaurare, ștergere, status încasare.
- Cote TVA mapate pe denumirile din cont, `Neplatitor TVA`, încasare la emitere, gestiune, email din SmartBill.
- Comenzile `smartbill:series` și `smartbill:taxes`. Teste Pest (coverage 100%), PHPStan nivel 8, CI.

### Eliminat
- Dependența de `App\Models\Invoice` / Auditor, produsele de audit, maparea județelor din aplicație.
- Modul „test” care nu trimitea nimic și încărcarea unui `.env` propriu al pachetului.

## 1.0.1

Versiunea pentru Auditor.
