import * as Character from "./character.js"

window.Character = Character;

const urlParams = new URLSearchParams(window.location.search);
const currentSeries = urlParams.get("series") ?? null;
const collection = document.getElementById("collection");

let count = 30;
let sort = "new";
let order = "desc";

const fetcher = async (url) => await fetch(url).then(res => res.json());

async function loadMore(entry, container)
{
  if (entry.isIntersecting)
  {
    // console.log(currentSeries);
    load(count += 30)
  }
}

async function loadSeries()
{
  const series = await fetcher(`./controllers/series.php`);
  for (const entry of document.querySelectorAll("select[name=series]"))
  {
    entry.innerHTML = series.map(({ uuid, name }) => uuid == currentSeries ? `<option selected value="${uuid}">${name}</option>` : `<option value="${uuid}">${name}</option>`)
  }
}

window.handleAddSeries = async function(event)
{
  event.preventDefault();
  const uuid = crypto.randomUUID();
  const formData = new FormData(event.target);
  formData.set("seriesName", formData.get("seriesName").trim());
  formData.set("uuid", uuid);
  await fetch("./controllers/series.php?action=create", {
    method: "POST",
    headers: { "content-type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(formData)
  });
  window.location.href = `./?series=${uuid}`;
}

window.goToSeries = uuid => window.location.href = `?series=${uuid}`

window.handleAddCharacter = async event =>
{
  console.log(event.target);
  event.preventDefault();
  const formData = new FormData(event.target);
  event.target.children[0].value = "";
  event.target.children[1].value = "";
  formData.set("characterName", Character.formatName(formData.get("characterName")));
  await fetch("./controllers/characters.php?action=create", {
    method: "POST",
    headers: { "content-type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(formData)
  }).then(_ => load(count));
}

window.sortCards = async function(value)
{
  sort = document.getElementById("sort").value;
  order = document.getElementById("order").value;
  await load(count);
}

async function load(count)
{
  const data = await fetcher(`./controllers/characters.php?action=view&series=${currentSeries}&sort=${sort}&order=${order}&amount=${count}`);
  collection.innerHTML = data.map(n => Character.Card(n)).join('');
}

window.onload = async function() {

  await loadSeries();
  await load(count);

  const observer = new IntersectionObserver(([entry]) => loadMore(entry, collection));

  observer.observe(document.getElementById("load"));

}