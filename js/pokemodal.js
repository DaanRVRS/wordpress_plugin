console.log("Pokemodal JS loaded");

function openPokeModal() {
    console.log("openPokeModal executed");
    document.getElementById('pokemodal-overlay').style.display = 'flex';
}

function closePokeModal() {
    console.log("closePokeModal executed");
    document.getElementById('pokemodal-overlay').style.display = 'none';
}