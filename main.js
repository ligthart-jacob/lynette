import * as Series from "./series.js"
import * as Character from "./character.js"

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

window.pasteClipBoard = async function(event)
{
  event.preventDefault();
  event.target.value = await navigator.clipboard.readText();
}

document.getElementById("sort").innerHTML = ["new", "name", "series", "obtained"].map(n => `<option ${n == (sessionStorage.getItem("sort") ?? "new") ? "selected" : ""} value="${n}">${n}</option>`)
document.getElementById("order").innerHTML = ["asc", "desc"].map(n => `<option ${n == (sessionStorage.getItem("order") ?? "desc") ? "selected" : ""} value="${n}">${n}</option>`)

observer.observe(document.getElementById("load"));