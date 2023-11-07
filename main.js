import * as Series from "./modules/series.js"
import * as Character from "./modules/character.js"

const batchSize = 30;

window.Character = Character;
window.Series = Series;

await Series.load();
await Character.load(batchSize);

const observer = new IntersectionObserver(async ([entry]) => {
  if (entry.isIntersecting) {
    await Character.load(Character.cards.length += batchSize);
  };
});

window.ondragover = event => event.preventDefault();
window.ondragenter = event => event.preventDefault();
window.ondrop = function(event)
{
  event.preventDefault();
  document.querySelector("#forms input[name=image]").value = event.dataTransfer.getData("text") ?? "";
}

window.pasteClipBoard = async function(event)
{
  event.preventDefault();
  event.target.value = await navigator.clipboard.readText();
}

document.getElementById("sort").innerHTML = ["new", "name", "series", "obtained"].map(n => `<option ${n == (sessionStorage.getItem("sort") ?? "new") ? "selected" : ""} value="${n}">${n}</option>`)
document.getElementById("order").innerHTML = ["asc", "desc"].map(n => `<option ${n == (sessionStorage.getItem("order") ?? "desc") ? "selected" : ""} value="${n}">${n}</option>`)

observer.observe(document.getElementById("load"));