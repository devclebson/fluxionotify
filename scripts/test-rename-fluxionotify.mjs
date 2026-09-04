import assert from "node:assert/strict";
import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";

const roots = ["setup.php", "hook.php", "inc", "front", "ajax", "src"];
const files = [];
function collect(path) {
  const stat = readdirSync(path, { withFileTypes: true });
  for (const entry of stat) {
    const child = join(path, entry.name);
    if (entry.isDirectory()) collect(child);
    else if (/\.(php|md)$/.test(child)) files.push(child);
  }
}
for (const root of roots) {
  if (root.includes(".")) files.push(root);
  else collect(root);
}
const source = files.map((file) => readFileSync(file, "utf8")).join("\n");

assert.match(source, /plugin_init_fluxionotify/);
assert.match(source, /plugin_version_fluxionotify/);
assert.match(source, /PluginFluxionotify/);
assert.match(source, /plugin_fluxionotify/);
assert.match(source, /glpi_plugin_fluxionotify_/);
assert.match(source, /GlpiPlugin\\Fluxionotify/);
assert.doesNotMatch(source, /PluginIflux|plugin_iflux|glpi_plugin_iflux_|GlpiPlugin\\Iflux/);

console.log("FluxIO Notify: identificadores técnicos renomeados.");
