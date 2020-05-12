#!/bin/bash
out="foc_csv.ocmod.zip"
[ -f "$out" ] && rm "$out"
zip -r9 --exclude=*.git* --exclude=*.DS_Store* "$out" ./upload ./install.xml