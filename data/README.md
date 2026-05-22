# Lorotom
lorotom.csv

Columns spec:
* our_code - supplier item code (external_id)
* producer_code - producer item code (mpn)
* name - item name (not important)
* producer - producer name
* quantity - available items count (*in case of >30* just replace with 31)
* price - price
* ean - ean


# Trah
trah.csv

Columns spec:
* 0 - supplier item code (external_id)
* 1 - available items count (*in case of >10* just replace with 11)
* 2 - price
* 3 - producer item code (mpn)
* 4 - ean
* 5 - producer name

Additionaly we should skip records with value "NARZEDZIA WARSZTAT" in column "5".