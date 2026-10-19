[![DOI](https://zenodo.org/badge/281618147.svg)](https://zenodo.org/badge/latestdoi/281618147)

> **Note:** This is the primary development repository. A dissemination copy is maintained under the [National Gallery GitHub organisation](https://github.com/national-gallery/dynamic-modelling).
> 
# dynamic-modelling
This is an interactive live modelling system which can automatically convert simple tab separated triples into graphical models using the [mermaid library](https://mermaid.ai/). 

## Instructions
The documentation is built into the tool itself, as models that you can explore and edit. Open the **Documentation** menu on the [Dynamic Modelling web site](https://research.ng-london.org.uk/modelling/), starting with **Getting started**. Each documentation page has a guided tour and links to the pages before and after it. The **Examples** menu shows models made with the tool. All of the related code is presented here in [GitHub](https://github.com/jpadfield/dynamic-modelling).

## Quick reference
Each topic below has its own page, with a guided tour, in the tool's **Documentation** menu.

### Writing a model
| Write | What it does |
|---|---|
| `Subject` *tab* `predicate` *tab* `object` | One statement (a triple) per line, drawn as two nodes joined by a labelled arrow |
| A fourth column: `subjectformat\|objectformat` | Sets the formats; either side can be left empty, and only the first appearance of a node counts |
| `// text` or `## text` | A comment: not drawn |
| Three columns pasted from a spreadsheet | The tabs come with them; lines with commas instead of tabs also work |

### Layout (first line)
| Write | What it does |
|---|---|
| `//flowchart LR` | Left to right (the default if the line is left out); also `TB`, `BT` and `RL` |
| `//graph TB` | The older form: draws the same, but cannot link whole subgraphs |
| `//flowchart LR fix` | Needed with older versions of Mermaid; makes no difference now |

### Subgraphs (boxes)
| Write | What it does |
|---|---|
| `//subgraph Title` ... `//end` | Groups everything first used between the two lines into a box |
| `//subgraph //Title` or `//subgraph` | Hides the title, or leaves it out |
| `//subgraph -TB Title` | Gives the box its own direction (only used when nothing in the box links outside it) |
| `//end base` | Styles the box with a named format |
| The box title used as a subject or object | Links to or from the whole box (needs `//flowchart`) |

### Formats
| Write | What it does |
|---|---|
| `object`, `event`, `actor`, `place`, `type`, `note`, `url`, ... | Named formats, all shown in the "Every format" example (`?example=example_formats`) |
| `note-fs24` | Adds a font size in pixels |
| `object-6-4` | Adds a dashed border: dash length then gap |
| `object-fs20-6-4` | Both together |

### Blank nodes and labels
| Write | What it does |
|---|---|
| `_BN` *tab* `has type` *tab* `Production` | Starts a new blank node; the `_BN` lines that follow belong to it |
| `_BN-1`, `_BN-2` | The blank node before the current one, and the one before that |
| `Painting#-1`, `Painting#-2` | Separate nodes that both show "Painting" |
| `crm:...`, `sci:...`, `dig:...`, `Type` | Get a separate copy for each subject (set in `config.json`) |

### Links, hover text and icons
| Write | What it does |
|---|---|
| `Text\|https://...` | A link (other sites open in a new tab) |
| `Text\|?example=name` | A link to another page of the tool |
| `Node` *tab* `tooltip` *tab* `Hover text` | Hover text; put a link on this line (`Node\|https://...`) to give a link its own hover text |
| `has note` as the predicate, or text in "double quotes" | Draws the object as a note |
| `aat:300033618`, `ulan:`, `tgn:`, `wd:` | Prefixes that become links (listed in `config.json`) |
| `fa:fa-paint-brush`, `far:`, `fab:` | Font Awesome 5 icons |
| `First line<br/>Second line` | A line break |

### Guided tours
| Write | What it does |
|---|---|
| `//tour Title` | Optional tour title |
| `//step Title \| Node A ; Node B \| Caption` | A step: names are node texts or subgraph titles (spaces and capitals are ignored); no names shows the whole model |
| `//sub Title \| Node A \| Caption` | A part of the step above: zooms in, keeps the step's highlighting; title and caption are optional |

Keys while a tour is open: arrow keys move back and forward, Shift with an arrow jumps a whole step, Space plays or pauses, Home restarts, C re-centres, H hides the controls and Esc closes.

### Web address options
| Add to the address | What it does |
|---|---|
| `?example=name` | Opens a model from the Documentation or Examples menu |
| `?url=https://...` | Opens a file of triples or JSON from another site |
| `?data=pako:...` | What **Copy Link** makes |
| `&simple` | Shows only the model and its tour, for embedding (not yet with `?data=pako:` addresses) |
| `&getmermaid` | Returns just the Mermaid code |

To show a model inside another web page:
```html
<iframe src="https://research.ng-london.org.uk/modelling/?example=doc_layout&simple"
        width="100%" height="600" style="border:0" title="Dynamic Modeller model"></iframe>
```

### Take care with
* **Commas:** a line without tabs that has two or more commas is split at the commas. Keep tour captions and comment lines to three commas or fewer.
* **Comments that start with the words subgraph or end** are read as instructions to start or end a box.
* **A node with the same text as a box title** becomes the box itself.
* **A box title mentioned inside another box** draws that box inside the other one.

## Acknowledgements

This software was originally developed at the National Gallery, London. Its current development is supported by:

### The [UKRI RICHeS](https://www.riches.ukri.org/) [HSDS](https://hsds.ac.uk/) project
[<img height="64px" src="graphics/HSDS-logo.png" alt="HSDS Logo">](https://hsds.ac.uk/)<br/>
* [HSDS is a project funded by UK Research and Innovation (UKRI) as part of the RICHeS Programme.](https://www.riches.ukri.org/)

### The Horizon Europe [ECHOES](https://www.echoes-eccch.eu/) project
[<img height="64px" src="graphics/ECHOES_Logo.png" alt="ECHOES Logo">](https://www.echoes-eccch.eu/)<br/>
* [ECHOES is a project funded by the European Commission under Grant Agreement n.101157364 – ECHOES.](https://cordis.europa.eu/project/id/101157364)
* ECHOES is a project funded by UK Research and Innovation (UKRI) under the UK government’s Horizon Europe funding guarantee n.10110142 & n.10110466.

### Previously supported by
[<img height="24px" src="graphics/IPERION-HS%20Logo.png" alt="IPERION-HS">](https://www.iperionhs.eu/)&nbsp;&nbsp;
[<img height="24px" src="graphics/sshoc-logo.png" alt="SSHOC">](https://sshopencloud.eu/)&nbsp;&nbsp;
[<img height="24px" src="graphics/UKRI_AHR_Council-Logo_Horiz-RGB.png" alt="AHRC">](https://www.ukri.org/councils/ahrc/)

* The H2020 [IPERION-HS](https://www.iperionhs.eu/) project
* The H2020 [SSHOC](https://sshopencloud.eu/) project, in which this software was first developed, prepared and tested
* The AHRC funded [Linked.Art](https://linked.art/) project
