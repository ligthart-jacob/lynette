
export const Card = ({ uuid, seriesUuid, name, series, obtained, image }) => `
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

export function formatName(name)
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

export function add()
{
  console.log("add");
}

export async function toggle(node, uuid)
{
  const obtained = node.parentNode.parentNode.parentNode.children[1];
  obtained.dataset.love = obtained.dataset.love == "0" ? "1" : "0";
  await fetch(`./controllers/characters.php?action=obtain&uuid=${uuid}&obtained=${obtained.dataset.love}`);
}

export function view()
{
  console.log("view");
}

export function update()
{
  console.log("update");
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