#!/bin/bash
out=foc_csv.ocmod.zip
if [[ -f "$out" ]]; then
  rm $out
fi
zip -r9 --exclude=*.git* --exclude=*.DS_Store* $out ./upload
