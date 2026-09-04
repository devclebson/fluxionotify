from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
EXCLUDED = {"docs/rename-fluxionotify-plan.md"}

for path in ROOT.rglob("*"):
    if not path.is_file() or ".git" in path.parts or path.relative_to(ROOT).as_posix() in EXCLUDED:
        continue
    if path.suffix.lower() not in {".php", ".md"}:
        continue
    text = path.read_text(encoding="utf-8")
    original = text
    replacements = [
        ("PluginIflux", "PluginFluxionotify"),
        ("plugin_iflux", "plugin_fluxionotify"),
        ("PLUGIN_IFLUX", "PLUGIN_FLUXIONOTIFY"),
        ("GlpiPlugin\\\\Iflux", "GlpiPlugin\\\\Fluxionotify"),
        ("/plugins/iflux", "/plugins/fluxionotify"),
        ("iflux_plugin", "fluxionotify"),
        ("iflux", "fluxionotify"),
        ("iFlux App Sync", "FluxIO Notify"),
        ("iFlux app", "FluxIO app"),
        ("aplicativo iFlux", "aplicativo FluxIO"),
        ("aplicativo mobile iFlux", "aplicativo mobile FluxIO"),
        ("plugin iFlux", "plugin FluxIO Notify"),
        ("Plugin iFlux", "Plugin FluxIO Notify"),
        ("do iFlux", "do FluxIO Notify"),
        ("iFlux", "FluxIO Notify"),
    ]
    for old, new in replacements:
        text = text.replace(old, new)
    if text != original:
        path.write_text(text, encoding="utf-8")
