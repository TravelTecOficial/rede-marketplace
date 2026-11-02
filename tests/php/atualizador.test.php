<?php
require '/t/bootstrap.php';
define( 'RTM_GITHUB_TOKEN', 'tok123' );
require '/p/rede-marketplace.php';

// Download do anexo em repo privado: a API (api.github.com) redireciona para uma URL assinada em
// *.githubusercontent.com, que recusa o cabeçalho Authorization. O token só vai para api.github.com.
$com = array( 'Authorization' => 'Bearer tok123', 'Accept' => 'application/octet-stream', 'User-Agent' => 'x' );
$sem = array( 'Accept' => 'application/octet-stream', 'User-Agent' => 'x' );
igual( $sem, rtm_cabecalhos_redirecionamento( $com, 'https://objects.githubusercontent.com/github-production-release-asset/1?X-Amz-Signature=abc' ), 'redirect para githubusercontent: tira o Authorization' );
igual( $com, rtm_cabecalhos_redirecionamento( $com, 'https://api.github.com/repos/TravelTecOficial/rede-marketplace/releases/assets/9' ), 'redirect dentro de api.github.com: mantém' );
igual( $sem, rtm_cabecalhos_redirecionamento( array( 'authorization' => 'Bearer tok123', 'Accept' => 'application/octet-stream', 'User-Agent' => 'x' ), 'https://evil.example/api.github.com' ), 'nome do cabeçalho em minúsculas e host parecido: tira' );
igual( $sem, rtm_cabecalhos_redirecionamento( $com, 'https://api.github.com.evil.example/x' ), 'host que só começa com api.github.com: tira' );
$outro = array( 'Authorization' => 'Bearer de-outro-plugin' );
igual( $outro, rtm_cabecalhos_redirecionamento( $outro, 'https://objects.githubusercontent.com/x' ), 'Authorization de outra requisição (não é o nosso token): não mexe' );
igual( $sem, rtm_cabecalhos_redirecionamento( $sem, 'https://objects.githubusercontent.com/x' ), 'sem Authorization: igual' );

// Gancho do Requests (WP_HTTP_Requests_Hooks repassa como requests-requests.before_redirect, com referências)
ok( in_array( 'rtm_antes_do_redirecionamento', $GLOBALS['rt_ganchos']['requests-requests.before_redirect'], true ), 'gancho requests-requests.before_redirect registrado' );
$destino = 'https://objects.githubusercontent.com/x';
$cab     = $com;
rtm_antes_do_redirecionamento( $destino, $cab );
igual( $sem, $cab, 'gancho altera os cabeçalhos do redirecionamento por referência' );
