let config
let prefix
let allClasses
let usedClasses
let pathinfo

function getCleanTriples(triplesTxt) {
  let lastLine = 0;
  let cleanData = [];

  let data = triplesTxt.split("\n");

  data.forEach((line, k) => {
    let trip = [];
    let m;

    if (/^.+\t.+\t.+$/g.test(line)) {
      trip = line.split("\t");
    } else if (/^.+[,].+[,].+$/g.test(line)) {
      trip = line.split(",");
    } else {
      trip = [line];
    }

    trip = trip.map((value) => value.trim());

    // Starting things with @ can upset mermaid
    trip = trip.map((tv) => {
      if (/^[\@](.+)$/g.test(tv)) {
        m = tv.match(/^[\@](.+)$/);
        return m[1];
      }
      return tv;
    });

    // only consider the first 4 values - removes spaces coming from spreadsheets
    trip = trip.slice(0, 4);

    // Considered as a data line
    if (trip[0]) {
      lastLine = k;
    }

    // Allow gaps of up to two lines between blocks of triples and remove others.
    if (k <= lastLine + 2) {
      cleanData.push(trip.join("\t"));
    }
  });

  return cleanData;
}

function Mermaid_defThing(varName, no, fc = false) {
  let prefix = config.prefix;
  let click = false;
  let code = "O" + no;
  let cls = "literal";

  for (let nm in prefix) {
    let a = prefix[nm];
    let regex = new RegExp("^" + a.match.short + "$");

    if (regex.test(varName)) {
      cls = a.format;
      
      if (a.url) {
        let pp = pathinfo(a.url);
        let tt = "Link to: " + pp.dirname + " ...";
        click = `click ${code} "${a.url}${m[1]}" "${tt}";\n`;
      } else if (/^http.+$/g.test(varName)) {
        click = `click ${code} "${varName}";\n`;
      }

      break;
    }
  }

  if (fc) {
    cls = fc;
  }

  if (allClasses[cls]) {
    usedClasses[cls] = allClasses[cls];
  } else {
    if (/^(.+)[-]([0-9]+)[-]([0-9]+)$/g.test(cls)) {
      let cm = cls.match(/^(.+)[-]([0-9]+)[-]([0-9]+)$/);
      
      if (allClasses[cm[1]]) {
        let tc = allClasses[cm[1]].trim().replace(/;$/, '');
        tc = tc.replace(new RegExp("classDef " + cm[1]), "classDef " + cls);
        tc += ",stroke-dasharray:" + cm[2] + " " + cm[3] + ";\n";
        allClasses[cls] = tc;
        usedClasses[cls] = allClasses[cls];
      }
    }
  }

  let str = `\n${code}("${varName}")\nclass ${code} ${cls};\n${click}`;

  return str;
}
