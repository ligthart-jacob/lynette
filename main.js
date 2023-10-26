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

observer.observe(document.getElementById("load"));