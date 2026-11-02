"""Gera dist/rede-marketplace.zip, o anexo da release do GitHub (o atualizador procura esse nome).

    python empacotar.py
"""
import re
import zipfile
from pathlib import Path

RAIZ = Path(__file__).parent
PLUGIN = RAIZ / "rede-marketplace"
INCLUIR = ["rede-marketplace.php", "readme.txt", "includes/*.php", "assets/*", "assets/teste/*", "templates/*.json", "templates/*.php"]


def main():
    v = re.search(r"^\s*\*\s*Version:\s*(.+)$", (PLUGIN / "rede-marketplace.php").read_text(encoding="utf-8"), re.M).group(1).strip()
    (RAIZ / "dist").mkdir(exist_ok=True)
    destino = RAIZ / "dist" / "rede-marketplace.zip"
    arquivos = sorted({c for p in INCLUIR for c in PLUGIN.glob(p) if c.is_file()})
    with zipfile.ZipFile(destino, "w", zipfile.ZIP_DEFLATED) as z:
        for arq in arquivos:
            z.write(arq, f"rede-marketplace/{arq.relative_to(PLUGIN).as_posix()}")
    print(f"{destino}  —  versão {v}, {len(arquivos)} arquivos")


if __name__ == "__main__":
    main()
