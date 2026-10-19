# Factory carton measurement reconciliation — 19 October 2026

Source: one handwritten customer photo of the factory's 3-ply and 5-ply carton weight/material table.

## What has been transcribed

`config/carton_factory_reference.php` lists **18 legible net-weight entries**,
indexed by the recorded carton dimensions in centimetres and ply (3 or 5).
These source values are provisional **transcriptions** pending the factory's
confirmation of the photograph; they are not assumed to be universal for
other customers or different paper grades.

The existing **Products & Materials → Weight / BOM audit** page now uses a
reference weight *only* where a client-master product has no weight of its
own, and both stored dimensions and ply exactly match the factory-sheet row.
The source is prominently labelled "Factory sheet reference". A product's
directly recorded weight always wins.

## Why the material BOM is deliberately unchanged

The sheet's other handwritten columns include paper/machine measurements,
but at this image resolution their headings, units and material identities
cannot be confirmed reliably. Several numbers appear larger than finished
net grams, possibly representing material consumed including cutting waste.
They MUST NOT be assigned to Kraft, Fluting, Test Liner, glue, etc. by guessing.

The current BOM is material-specific and affects FIFO roll stock, production
cost, accounting and historical reproduction. Finished weight is not raw
material usage. **No automatic material deduction, BOM recipe, default GSM,
adhesive %, wastage %, business-unit scope, cost or historical ledger entry
has changed.** This branch adds the source observations to the audit so
errors are visible and can be resolved before an approved BOM revision.

## Exact source validation needed before material-by-material calibration

1. Get a straight, high-resolution scan (or sheet/Excel) with readable
   column headings, raw material names, units, and per-carton quantities.
2. Confirm what the right-side net-weight column measures (grams per
   one finished carton, including what printing/adhesive moisture).
3. Confirm the paper columns are per finished carton or per roll/batch,
   and how the left-margin R/S notes affect cut lengths, yield and scrap.
4. Confirm the paper grade/GSM/number of sheets for each 3-ply and 5-ply
   reference separately.
5. Approve a new BOM version for future production, maintaining old
   snapshots, and verify cost and batch consumption with factory numbers.

## Sanity checks

- `php artisan test --filter=FactoryCartonReferenceTest`
- Existing `FinishedGoodsCsvImportTest` and
  `FinancialReleaseInvariantsTest` guard manual override and cost flows.
- A factory-sheet-only weight does not write `products.finished_weight_g`.
  If the factory approves it for a *specific* product, import it with the
  existing finished-goods CSV workflow (using stable product IDs).
