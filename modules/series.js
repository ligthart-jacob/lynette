
const containers = [...document.querySelectorAll("select[name=series]")];

const fetcher = async (url) => await fetch(url).then(res => res.json());

export const overlay = {
  node: document.getElementById("overlay"),
  form: document.getElementById("seriesUpdate"),
  open: function(event) {
    if (event.button != 4) return;
    event.preventDefault();
    this.node.style.display = "flex";
    this.form.style.display = "flex";
  },
  close: function() { this.node.style.display = "none"; this.form.style.display = "none"; },
}

export async function copy(event)
{
  event.preventDefault();
  navigator.clipboard.writeText(event.target.innerText);
}

export async function formHandler(event)
{
  event.preventDefault();
  const formData = new FormData(event.target);
  formData.set("name", formData.get("name").trim());
  const result = await fetch("./controllers/series.php?action=create", {
    method: "POST",
    body: formData
  }).then(res => res.text());
  window.location.href = `./?series=${result}`;
}

export async function updateFormHandler(event)
{
  event.preventDefault();
  const formData = new FormData(event.target, event.submitter);
  if (formData.get("action") == "update" && formData.get("name") == "") return alert("name field is empty");
  await fetch(`./controllers/series.php?action=${formData.get("action")}`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded"},
    body: new URLSearchParams(formData)
  }).then(res => {
    if (res.ok) load();
    else if (res.status == 405) alert("Series contains characters, remove them before proceding");
  })
}

export async function load()
{
  const series = await fetcher(`./controllers/series.php`);
  for (const entry of containers)
  {
    entry.innerHTML = series.map(({ slug, name }) => slug == Character.config.current ? `<option selected value="${slug}">${name}</option>` : `<option value="${slug}">${name}</option>`)
  }
}

export const move = slug => window.location.href = `?series=${slug}`;