getByString = (o, s) => {
  s = s.replace(/\[(\w+)\]/g, ".$1"); // convert indexes to properties
  s = s.replace(/^\./, ""); // strip a leading dot
  var a = s.split(".");
  for (var i = 0, n = a.length; i < n; ++i) {
    var k = a[i];
    if (k in o) {
      o = o[k];
    } else {
      return;
    }
  }
  return o;
};

window.addEventListener("load", () => {
  const paths = [];
  // Load paths from non js menu
  document
    .querySelectorAll(".article-content nav.wiki-nav > div > div")
    .forEach(div => {
      const path = div.innerText
        .split("/")
        .map(t => t.trim())
        .join("/");
      if (path) paths.push({ path: path, href: div.querySelector("a").href });
    });
  // Construct file structure
  const hierarchy = paths.reduce(function(hier, path) {
    var x = hier;
    path.path.split("/").forEach(function(item) {
      if (!x[item]) {
        x[item] = {
          path:
            path.path
              .split("/")
              .slice(0, -1)
              .pop() || path.path
        };
      }
      x = x[item];
    });
    x.file = path.path
      .split("/")
      .slice(-1)
      .pop();
    x.href = path.href;
    return hier;
  }, {});
  // Print it back out as interactive menu
  const pathSorter = (a, b) =>
    `${a.path}${a.file}` > `${b.path}${b.file}` ? -1 : 1;
  const isMenu = obj => {
    const test = { ...obj };
    delete test["href"];
    delete test["file"];
    delete test["path"];
    return !!Object.keys(test).length;
  };
  const print = paths => {
    return paths.reduce((acc, curr, idx, arr) => {
      if (typeof curr !== "string") {
        const expander = isMenu(curr) ? '<a class="wiki-expander">+</a>' : "";
        const paths = print(Object.values(curr).sort(pathSorter));
        const filename = curr.file
          ? `<li ><a href="${curr.href}">${curr.file}</a>${expander}</li>`
          : `<li>${curr.path}${expander}</li>`;
        return (
          `${filename}${paths ? `<ul class="nodisplay">${paths}</ul>` : ""}` +
          acc
        );
      }
      return acc;
    }, "");
  };
  html = `<a class="wiki-button wiki-nav-button">Wiki Menu</a><ul class="nodisplay">${print(
    Object.values(hierarchy).sort(pathSorter)
  )}</ul>`;
  document.querySelector("nav.wiki-nav").innerHTML = html;
  // Make breadcrumbs better
  const breadcrumbs = document.querySelectorAll("nav.wiki-breadcrumbs span");
  if (breadcrumbs)
    Array.from(breadcrumbs).forEach(span => {
      if (!span.dataset.path) return;
      if (
        !Object.keys(getByString(hierarchy, span.dataset.path)).includes("file")
      ) {
        span.innerHTML = span.querySelector("a").innerHTML;
        span.classList.add("wiki-button");
        span.classList.add("disabled");
      } else {
        span.querySelector("a").classList.add("wiki-button");
      }
    });

  // Add click handler
  Array.from(document.querySelectorAll("nav .wiki-expander")).forEach(a => {
    a.addEventListener("click", e => {
      e.target.parentElement.nextSibling.classList.toggle("nodisplay");
      e.target.innerHTML = e.target.innerHTML === "+" ? "-" : "+";
    });
  });
  document.querySelector(".wiki-nav-button").addEventListener("click", e => {
    e.target.nextSibling.classList.toggle("nodisplay");
  });
});
