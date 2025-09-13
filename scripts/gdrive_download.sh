#!/usr/bin/env bash
fileid="$1"
filename="$2"
cookie="/tmp/cookie_$fileid.txt"
confirm=$(curl -sc "$cookie" "https://drive.google.com/uc?export=download&id=$fileid" | sed -rn 's/.*confirm=([0-9A-Za-z_]+).*//p')
if [ -n "$confirm" ]; then url="https://drive.google.com/uc?export=download&confirm=$confirm&id=$fileid"; else url="https://drive.google.com/uc?export=download&id=$fileid"; fi
curl -Lb "$cookie" "$url" -o "$filename"
