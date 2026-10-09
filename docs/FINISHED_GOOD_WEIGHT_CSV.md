# Finished-good weights and repeatable CSV imports

The product master now stores **finished_weight_g** (grams per finished carton) and an optional **sku**.
Historical 171 client cartons are preserved and can be updated by importing their existing \`product_id\`.
For new cartons, set a unique SKU. Importing the same SKU a second time updates it rather than creating another record.

Download the sample from **Products & Materials → Import finished goods → Download CSV template**.
The CSV headers are:

\`\`\`csv
product_id,sku,name,category,unit,weight_g,length_mm,width_mm,height_mm,ply,flute_type,color_count,printing_type,finish_type,print_spec,reel_cut,pieces_per_carton,pack_description,description,is_active
\`\`\`

- Mandatory: **name**, **category** (an existing category, matched by name), **weight_g** (positive grams).
- Identity: **product_id** for existing records or **sku** for new records; both may be provided if consistent.
- **unit** defaults to \`pcs\` for new records. Existing unit is preserved if omitted.
- Dimensions are **mm**. Blank optional fields preserve existing values. Unspecified active status is preserved.
- Files accept UTF-8 CSV with a header, normal quoted commas, up to 2 MB / 5,000 rows. Entire import is transactional: a bad row rejects everything.
- A separate \`finished-good-csv:<product_id>\` specification snapshot is created/updated. Existing client source rows are retained.

## Physical inventory safety

Importing total finished carton weight does **not** divide that weight among raw materials:
paper liners, fluting, ink, glue and moisture need material-specific BOM recipes. The **BOM remains authoritative** for stock deductions and cost.
Current production start/completion already uses \`ProductionQuantityService\`, \`StockDeductionService\`, and material-specific BOM requirements, while roll purchases carry known kg/batch.
Do not scale BOMs by finished weight without verifying GSM, layer counts, flute take-up, glue/ink, scrap, and approved wastage.

Import never adjusts stock, reopens orders, backfills roll purchases, or modifies/prices/approves BOMs. After import, review discrepancies between measured carton weight and BOM theoretical materials before approving BOM amendments.
