<?php
/*
 * Plugin Name: Pokemodal
 */

defined('ABSPATH') || exit;

/* ---------------------------------------------------
 * Scripts
 * --------------------------------------------------- */
function pokemodal_enqueue_scripts() {
    wp_enqueue_script(
        'pokemodal',
        plugin_dir_url(__FILE__) . 'js/pokemodal.js',
        [],
        '1.0',
        true
    );

    wp_localize_script( 'pokemodal', 'PokemonAjax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'pokemon_nonce' ),
    ]);
}
add_action('wp_enqueue_scripts', 'pokemodal_enqueue_scripts');


/* ---------------------------------------------------
 * Button & Modal HTML
 * --------------------------------------------------- */
function pokemodal_display_button_container() {
    echo "<div style='position:fixed;bottom:20px;right:20px;z-index:9999;'>
            <button onclick='openPokeModal()'
                style='text-align:center;border-radius:12px;
                background-color:red;color:#ffffff;padding:10px 20px;
                cursor:pointer;border:none;min-width:125px;min-height:40px;'>
                Pokemodal
            </button>
          </div>";
}
add_action('wp_footer', 'pokemodal_display_button_container');


function pokemodal_create_modal() {
?>
<style>
#pokemodal-overlay {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:99999;
    align-items:center;
    justify-content:center;
}
#pokemodal-box {
    background:#fff;
    width:90%;
    max-width:450px;
    padding:25px;
    border-radius:14px;
    text-align:center;
    font-family:Arial,sans-serif;
}
#pokemodal-close {
    position:absolute;
    top:12px;
    right:16px;
    font-size:26px;
    cursor:pointer;
    color:#fff;
}
#pokemodal-search {
    background:#e63946;
    color:#fff;
    padding:12px;
    width:100%;
    border:none;
    border-radius:6px;
    cursor:pointer;
    margin-top:10px;
}
</style>

<div id="pokemodal-overlay">
    <div id="pokemodal-box">
        <span id="pokemodal-close" onclick="closePokeModal()">&times;</span>

        <h2>PokéModal</h2>
        <p>Find your favourite Pokémon</p>

        <input id="pokemodal-input" type="text" placeholder="Enter Pokémon name..." 
        style="width:calc(100% - 24px);padding:12px;border-radius:6px;border:1px solid #ccc;">
        <button id="pokemodal-search">Search</button>

        <div id="pokemon-result" style="margin-top:15px;"></div>
    </div>
</div>

<script>
function openPokeModal() {
    document.getElementById('pokemodal-overlay').style.display = 'flex';
}
function closePokeModal() {
    document.getElementById('pokemodal-overlay').style.display = 'none';
}

document.getElementById('pokemodal-search').addEventListener('click', function () {
    const pokemon = document.getElementById('pokemodal-input').value.trim();
    if (!pokemon) return;

    document.getElementById('pokemon-result').innerHTML = 'Loading...';

    fetch(PokemonAjax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'pokemon_search',
            pokemon: pokemon,
            _ajax_nonce: PokemonAjax.nonce
        })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            document.getElementById('pokemon-result').innerHTML = res.data;
            return;
        }

        const p = res.data;
        document.getElementById('pokemon-result').innerHTML = `
            <h3>${p.name.toUpperCase()}</h3>
            <img src="${p.sprites.front_default}">
            <p>Height: ${p.height}</p>
            <p>Weight: ${p.weight}</p>
        `;
    });
});
</script>
<?php
}
add_action('wp_footer', 'pokemodal_create_modal');


/* ---------------------------------------------------
 * AJAX handler
 * --------------------------------------------------- */
add_action( 'wp_ajax_pokemon_search', 'pokemodal_handle_pokemon_search' );
add_action( 'wp_ajax_nopriv_pokemon_search', 'pokemodal_handle_pokemon_search' );

function pokemodal_handle_pokemon_search() {

    check_ajax_referer( 'pokemon_nonce' );

    $pokemon = sanitize_text_field( $_POST['pokemon'] ?? '' );

    if ( empty( $pokemon ) ) {
        wp_send_json_error( 'No Pokémon provided' );
    }

    $response = wp_remote_get(
        'https://pokeapi.co/api/v2/pokemon/' . strtolower( $pokemon ),
        [ 'timeout' => 10 ]
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
        wp_send_json_error( 'Pokémon not found' );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    wp_send_json_success( $data );
}
