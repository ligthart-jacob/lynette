
const urlParams = new URLSearchParams(window.location.search);
const fetcher = async (url) => await fetch(url).then(res => res.json());

export let cards = [];

export const config = {
  container: document.getElementById("collection"),
  current: urlParams.get("series") ?? null,
  search: null,
  count: 0
};

// Character.overlay.open('${uuid}')

const Card = ({ uuid, slug, name, series, obtained, image }) => `
  <div class="card">
    <div class="overlay" onmouseup="Character.overlay.open(event, '${uuid}')">
      <div class="head">
        <button onclick="Character.toggle(event.target, '${uuid}')">&#xf004;</button>
        <button onclick="Character.remove(event.target, '${uuid}')">&#xf05e;</button>
      </div>
      <div class="body">
        <h3 oncontextmenu="Character.copy(event)">${name}</h3>
        <a oncontextmenu="Series.copy(event)" href="?series=${slug}">${series}</a>
      </div>
    </div>
    <img data-love="${obtained}" src=".${image}">       
  </div>
`;

export const overlay = {
  node: document.getElementById("overlay"),
  form: document.getElementById("characterUpdate"),
  open: function(event, uuid) {
    if (event.button != 4) return;
    event.preventDefault();
    const card = cards.filter(n => n.uuid == uuid)[0];
    this.node.querySelector("input[name=uuid]").value = card.uuid; 
    this.node.querySelector("input[name=prevImage]").value = card.image; 
    this.node.querySelector("input[name=name]").value = card.name;
    const image = this.node.querySelector("input[name=image]");
    if (image.type == "file") image.type = "text";
    image.value = card.image;
    this.node.querySelector("select[name=series]").value = card.slug;
    this.node.style.display = "flex";
    this.form.style.display = "flex";
  },
  close: function() { this.node.style.display = "none"; this.form.style.display = "none"; },
}

export async function copy(event)
{
  event.preventDefault();
  navigator.clipboard.writeText(event.target.innerText.split(', ').reverse().join(' '));
}

export async function updateFormHandler(event)
{
  event.preventDefault();
  const formData = new FormData(event.target);
  const [ firstname, lastname ] = formatName(formData.get("name"));
  formData.delete("name");
  formData.set("firstname", firstname);
  formData.set("lastname", lastname);
  // Submit the form data to the controller
  await fetch("./controllers/characters.php?action=update", {
    method: "POST",
    body: formData
  }).then(res => res.text()).then(image => {
    event.target.querySelector("input[name=prevImage]").value = image; 
    load(cards.length);
  });
}

export async function formHandler(event)
{
  event.preventDefault();
  const formData = new FormData(event.target);
  event.target.children[0].value = "";
  event.target.children[1].value = "";
  const [ firstname, lastname ] = formatName(formData.get("name"));
  formData.delete("name");
  formData.set("firstname", firstname);
  formData.set("lastname", lastname);
  // Submit the form data to the controller
  await fetch("./controllers/characters.php?action=create", {
    method: "POST",
    body: formData
  }).then(_ => load(cards.length < 15 ? 15 : cards.length));
}

export async function load(count = cards.length)
{
  cards = await fetcher(`./controllers/characters.php?action=view&series=${config.current}&search=${config.search}&sort=${sessionStorage.getItem("sort") ?? "new"}&order=${sessionStorage.getItem("order") ?? "desc"}&amount=${count}`);
  view();
}

export async function toggle(node, uuid)
{
  const obtained = node.parentNode.parentNode.parentNode.children[1];
  obtained.dataset.love = obtained.dataset.love == "0" ? "1" : "0";
  await fetch(`./controllers/characters.php?action=obtain&uuid=${uuid}&obtained=${obtained.dataset.love}`);
}

export async function sort()
{
  sessionStorage.setItem("sort", document.getElementById("sort").value);
  sessionStorage.setItem("order", document.getElementById("order").value);
  load();
}

export async function remove(node, uuid)
{
  const image = node.parentNode.parentNode.nextElementSibling.src;
  node.parentNode.parentNode.parentNode.remove();
  await fetch(`./controllers/characters.php?action=remove`, 
  {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded"
    },
    body: new URLSearchParams({ uuid, image })
  });
}

function view()
{
  config.container.innerHTML = cards.map(card => Card(card)).join('');
}

function formatName(name)
{
  name = name.trim();
  if (name.includes('\\'))
  {
    return [name.replaceAll('\\', ''), 0];
  }
  else if (name.includes(','))
  {
    const matches = name.match(/(.+),\s+(.+)/);
    return [matches[1], matches[2]];
  }
  else
  {
    const [ firstname, ...lastname ] = name.match(/\w+/g);
    return [firstname, lastname.length ? lastname.join(' ') : 0];
  }
}

export async function search(query)
{
  config.search = query == "" ? null : encodeURIComponent(query.trim());
  load(cards.length < 15 ? 15 : cards.length);
}