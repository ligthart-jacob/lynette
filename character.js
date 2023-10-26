
const urlParams = new URLSearchParams(window.location.search);
const fetcher = async (url) => await fetch(url).then(res => res.json());

export let cards = [];

export const config = {
  container: document.getElementById("collection"),
  current: urlParams.get("series") ?? null,
  sort: "new",
  order: "DESC",
  count: 0
};

const Card = ({ uuid, seriesUuid, name, series, obtained, image }) => `
  <div class="card">
    <div class="overlay">
      <div class="head">
        <button onclick="Character.toggle(event.target, '${uuid}')">&#xf004;</button>
        <button onclick="Character.remove(event.target, '${uuid}')">&#xf05e;</button>
      </div>
      <div class="body">
        <h3>${name}</h3>
        <a href="?series=${seriesUuid}">${series}</a>
      </div>
    </div>
    <img data-love="${obtained}" src=".${image}">       
  </div>
`;

export async function formHandler(event)
{
  event.preventDefault();
  const formData = new FormData(event.target);
  event.target.children[0].value = "";
  event.target.children[1].value = "";
  formData.set("name", formatName(formData.get("name")));
  // Submit the form data to the controller
  await fetch("./controllers/characters.php?action=create", {
    method: "POST",
    headers: { "content-type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(formData)
  }).then(_ => load(cards.length));
}

export async function load(count = cards.length)
{
  cards = await fetcher(`./controllers/characters.php?action=view&series=${config.current}&sort=${config.sort}&order=${config.order}&amount=${count}`);
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
  config.sort = document.getElementById("sort").value;
  config.order = document.getElementById("order").value;
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
  // Remove trailing whitespace
  name = name.trim();
  if (name.includes(' '))
  {
    if (name.includes('\\'))
    {
      return name.replace('\\', '');
    }
    else if (!name.includes(','))
    {
      const matches = name.match(/([^\s]*)\s(.*)/);
      matches.shift();
      return matches.reverse().join(', ');
    }
  }
  return name;
}