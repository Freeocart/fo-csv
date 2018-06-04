#!/bin/bash
out=foc_csv.ocmod.zip
if [[ -f "$out" ]]; then
  rm $out
fi
zip -r9 $out ./upload
