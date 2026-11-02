# Rede Marketplace

Plugin WordPress: marketplace de agentes de viagens da Rede Turística (redeturistica.com.br).

- Testes: `cd tests && npm install && node run.mjs` (PHP 7.4 e 8.2 por php-wasm).
- Versão nova: subir `Version:` e `RTM_VERSION`, entrada no `readme.txt`, `python empacotar.py`,
  commit, push e `gh release create vX.Y.Z dist/rede-marketplace.zip`. O anexo tem de se chamar `rede-marketplace.zip`.
- Prefixos: funções/opções/meta `rtm_`, constantes `RTM_`, text domain `rede-marketplace`.
