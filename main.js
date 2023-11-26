import * as Series from "./modules/series.js"
import * as Character from "./modules/character.js"

const batchSize = 30;

const store = {
  image: null
}

window.Character = Character;
window.Series = Series;

window.test = async function()
{
  const formData = new FormData();
  formData.set("image", "/cards/7c517b7e0476477c3536306ac2b12a60cb7e24a3e04f791d24222d9807fe88b5.png");
  await fetch("./controllers/characters.php?action=test", {
    method: "POST",
    body: formData
  }).then(res => res.text()).then(data => console.log(data));
}

await Series.load();
await Character.load(batchSize);

const observer = new IntersectionObserver(async ([entry]) => {
  if (entry.isIntersecting) {
    await Character.load(Character.cards.length += batchSize);
  };
});

window.ondragover = event => event.preventDefault();
window.ondragenter = event => event.preventDefault();
// window.ondrop = function(event)
// {
//   event.preventDefault();
//   document.querySelector("#forms input[name=image]").value = event.dataTransfer.getData("text") ?? "";
//   document.querySelector("#addCharacter ").click();
// }

window.toggleInput = async function(event)
{
  event.preventDefault();
  if (event.shiftKey)
  {
    if (event.target.type == "text")
    {
      store.image = event.target.value;
      event.target.type = "file";
    }
    else
    {
      event.target.type = "text";
      event.target.value = store.image;
    }
  }
  else if (event.target.type == "text")
  {
    event.target.value = await navigator.clipboard.readText();
  }
}

window.pasteClipBoard = async function(event)
{
  event.preventDefault();
  event.target.value = await navigator.clipboard.readText();
}

document.getElementById("sort").innerHTML = ["new", "name", "series", "obtained"].map(n => `<option ${n == (sessionStorage.getItem("sort") ?? "new") ? "selected" : ""} value="${n}">${n}</option>`)
document.getElementById("order").innerHTML = ["asc", "desc"].map(n => `<option ${n == (sessionStorage.getItem("order") ?? "desc") ? "selected" : ""} value="${n}">${n}</option>`)

observer.observe(document.getElementById("load"));