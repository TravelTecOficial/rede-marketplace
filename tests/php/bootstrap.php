<?php
// Base dos testes: constantes, stubs mínimos do WordPress e verificadores. Não vai no zip.
define( 'ABSPATH', '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'RTM_TESTE', true );

$GLOBALS['rt_falhas']     = 0;
$GLOBALS['rt_opcoes']     = array();
$GLOBALS['rt_transients'] = array();   // chave => [valor, ttl]
$GLOBALS['rt_http']       = array();   // fila de respostas falsas para wp_remote_request
$GLOBALS['rt_http_log']   = array();   // [url, args] de cada chamada
$GLOBALS['rt_ganchos']    = array();   // gancho => [callbacks]
$GLOBALS['rt_meta']       = array();   // post_id => [chave => valor]
$GLOBALS['rt_posts']      = array();   // lista de objetos post falsos (ID, post_type, post_status...)
$GLOBALS['rt_termos']     = array();   // post_id => [taxonomia => [objetos termo]]
$GLOBALS['rt_tipos']      = array();   // register_post_type
$GLOBALS['rt_taxonomias'] = array();   // register_taxonomy
$GLOBALS['rt_anexos']     = array();   // id => url da imagem

function ok( $cond, $msg ) {
	echo ( $cond ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $cond ) { $GLOBALS['rt_falhas']++; }
}
function igual( $esperado, $atual, $msg ) {
	$bom = $esperado === $atual;
	echo ( $bom ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $bom ) {
		echo '      esperado: ' . var_export( $esperado, true ) . "\n      atual:    " . var_export( $atual, true ) . "\n";
		$GLOBALS['rt_falhas']++;
	}
}
function contem( $trecho, $texto, $msg ) { ok( false !== strpos( (string) $texto, $trecho ), $msg ); }
function nao_contem( $trecho, $texto, $msg ) { ok( false === strpos( (string) $texto, $trecho ), $msg ); }
function fixture( $nome ) { return json_decode( file_get_contents( '/f/' . $nome . '.json' ), true ); }
function resposta_falsa( $codigo, $corpo, $cabecalhos = array() ) {
	$GLOBALS['rt_http'][] = array( 'response' => array( 'code' => $codigo ), 'body' => is_string( $corpo ) ? $corpo : json_encode( $corpo ), 'headers' => $cabecalhos );
}

class WP_Error {
	public $code; public $message; public $data;
	public function __construct( $code = '', $message = '', $data = '' ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }

function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $s ) { return (string) $s; }
function wp_json_encode( $d, $f = 0 ) { return json_encode( $d, $f ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_kses_post( $s ) { return preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $s ); }
function remove_accents( $s ) {
	return strtr( $s, array( 'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ç'=>'C' ) );
}
function sanitize_title( $s ) {
	$s = strtolower( remove_accents( (string) $s ) );
	$s = preg_replace( '/[^a-z0-9]+/', '-', $s );
	return trim( $s, '-' );
}
function home_url( $p = '' ) { return 'https://exemplo.test' . $p; }
function admin_url( $p = '' ) { return 'https://exemplo.test/wp-admin/' . $p; }
function get_bloginfo( $k ) { return 'Rede Turística'; }
function wp_trim_words( $t, $n = 55, $mais = '…' ) {
	$p = preg_split( '/\s+/', trim( strip_tags( $t ) ) );
	return count( $p ) > $n ? implode( ' ', array_slice( $p, 0, $n ) ) . $mais : implode( ' ', $p );
}

function get_option( $k, $padrao = false ) { return array_key_exists( $k, $GLOBALS['rt_opcoes'] ) ? $GLOBALS['rt_opcoes'][ $k ] : $padrao; }
function update_option( $k, $v, $autoload = null ) { $GLOBALS['rt_opcoes'][ $k ] = $v; return true; }
function get_transient( $k ) { return isset( $GLOBALS['rt_transients'][ $k ] ) ? $GLOBALS['rt_transients'][ $k ][0] : false; }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['rt_transients'][ $k ] = array( $v, $ttl ); return true; }
function delete_transient( $k ) { unset( $GLOBALS['rt_transients'][ $k ] ); return true; }
function get_site_transient( $k ) { return get_transient( $k ); }
function set_site_transient( $k, $v, $ttl = 0 ) { return set_transient( $k, $v, $ttl ); }
function delete_site_transient( $k ) { return delete_transient( $k ); }

function wp_remote_request( $url, $args ) {
	$GLOBALS['rt_http_log'][] = array( $url, $args );
	if ( ! $GLOBALS['rt_http'] ) { return new WP_Error( 'http_request_failed', 'sem resposta falsa na fila' ); }
	return array_shift( $GLOBALS['rt_http'] );
}
function wp_remote_get( $url, $args = array() ) { return wp_remote_request( $url, $args ); }
function wp_remote_head( $url, $args = array() ) { return wp_remote_request( $url, $args ); }
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? $r['response']['code'] : ''; }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? $r['body'] : ''; }
function wp_remote_retrieve_header( $r, $h ) { return is_array( $r ) && isset( $r['headers'][ $h ] ) ? $r['headers'][ $h ] : ''; }

// Ganchos: add_* só registram; do_action chama os callbacks registrados.
function add_action( $g, $cb, $p = 10, $n = 1 ) { $GLOBALS['rt_ganchos'][ $g ][] = $cb; }
function add_filter( $g, $cb, $p = 10, $n = 1 ) { $GLOBALS['rt_ganchos'][ $g ][] = $cb; }
function do_action( $g, ...$args ) {
	foreach ( isset( $GLOBALS['rt_ganchos'][ $g ] ) ? $GLOBALS['rt_ganchos'][ $g ] : array() as $cb ) { call_user_func_array( $cb, $args ); }
}
function register_activation_hook( $f, $cb ) {}
function plugin_dir_path( $f ) { return '/p/'; }
function plugin_dir_url( $f ) { return 'https://exemplo.test/wp-content/plugins/rede-marketplace/'; }
function plugin_basename( $f ) { return 'rede-marketplace/rede-marketplace.php'; }

// Tipos e taxonomias: só guardam a definição.
function register_post_type( $tipo, $args = array() ) { $GLOBALS['rt_tipos'][ $tipo ] = $args; return true; }
function register_taxonomy( $tax, $tipos, $args = array() ) { $GLOBALS['rt_taxonomias'][ $tax ] = array( 'tipos' => $tipos, 'args' => $args ); return true; }

// Meta de post em memória.
function get_post_meta( $id, $chave = '', $unico = false ) {
	$m = isset( $GLOBALS['rt_meta'][ $id ] ) ? $GLOBALS['rt_meta'][ $id ] : array();
	if ( '' === $chave ) { return $m; }
	if ( ! array_key_exists( $chave, $m ) ) { return $unico ? '' : array(); }
	return $unico ? $m[ $chave ] : array( $m[ $chave ] );
}
function update_post_meta( $id, $chave, $valor ) { $GLOBALS['rt_meta'][ $id ][ $chave ] = $valor; return true; }

// get_posts: filtra $GLOBALS['rt_posts'] por post_type e post_status.
function get_posts( $args = array() ) {
	$res = array();
	foreach ( $GLOBALS['rt_posts'] as $p ) {
		if ( isset( $args['post_type'] ) && $p->post_type !== $args['post_type'] ) { continue; }
		if ( isset( $args['post_status'] ) && $p->post_status !== $args['post_status'] ) { continue; }
		$res[] = $p;
	}
	return $res;
}

// Termos.
function get_the_terms( $id, $tax ) {
	return isset( $GLOBALS['rt_termos'][ $id ][ $tax ] ) ? $GLOBALS['rt_termos'][ $id ][ $tax ] : false;
}
function term_exists( $termo, $tax = '' ) {
	foreach ( $GLOBALS['rt_taxonomias'] as $t => $d ) {
		if ( $tax && $t !== $tax ) { continue; }
		if ( isset( $d['criados'][ $termo ] ) ) { return array( 'term_id' => $d['criados'][ $termo ] ); }
	}
	return 0;
}
function wp_insert_term( $termo, $tax, $args = array() ) {
	static $n = 0;
	$n++;
	$GLOBALS['rt_taxonomias'][ $tax ]['criados'][ $termo ] = $n;
	return array( 'term_id' => $n );
}

function get_permalink( $id ) { return 'https://exemplo.test/?p=' . (int) $id; }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return isset( $GLOBALS['rt_anexos'][ $id ] ) ? $GLOBALS['rt_anexos'][ $id ] : false; }
