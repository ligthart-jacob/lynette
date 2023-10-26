
const containers = [...document.querySelectorAll("select[name=series]")];

console.log(containers);

const fetcher = async (url) => await fetch(url).then(res => res.json());

export async function formHandler(event)
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

export async function load()
{
  const series = await fetcher(`./controllers/series.php`);
  for (const entry of containers)
  {
    entry.innerHTML = series.map(({ uuid, name }) => uuid == Character.config.current ? `<option selected value="${uuid}">${name}</option>` : `<option value="${uuid}">${name}</option>`)
  }
}

export const move = uuid => window.location.href = `?series=${uuid}`;