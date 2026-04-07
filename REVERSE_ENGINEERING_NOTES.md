
## Binaires ELF
```bash
# lister quelques chaînes
strings sample.bin | head

# désassembler le point d'entrée
objdump -d sample.bin | less

# infos ELF
readelf -h sample.bin
```

## APK Android
```bash
# extraire et lire le manifest
apktool d app.apk -o out_apk
cat out_apk/AndroidManifest.xml

# décompiler en Java lisible
jadx -d out_jadx app.apk
```

## Outils utiles
- Ghidra : analyse statique/dynamique multi‑archi
- radare2 / rizin : alternative CLI à IDA
- mitmproxy / Wireshark : inspection réseau
