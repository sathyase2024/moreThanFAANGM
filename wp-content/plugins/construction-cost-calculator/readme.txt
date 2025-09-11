=== Construction Cost Calculator ===
Contributors: your-team
Tags: calculator, construction, estimate, cost, builder
Requires at least: 5.5
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later

== Description ==
Simple, lightweight construction cost estimator. Adds a responsive calculator via shortcode.

== Usage ==
1. Upload the `construction-cost-calculator` folder to `/wp-content/plugins/` and activate.
2. Add the shortcode to any page or post:

`[construction_calculator title="Architectural Construction Cost Calculator 2025 (Tamilnadu)"]`

== Filters ==
`construction_calculator_packages` — Filter the packages and base per-sqft rates.
`construction_calculator_rates` — Modify non-package rates (sump, septic, wall).
`construction_calculator_works` — Add or customize table rows.

== Settings ==
Go to Settings → Construction Calculator.
- Packages (JSON) example:
```
{"standard":2099,"premium":2399,"luxury":2699}
```
- Rates (JSON) example:
```
{"sump_rate":24,"septic_rate":24,"wall_rate":425}
```
- Works (JSON) example:
```
[
  {"key":"builtup","label":"Built-up Area","unit":"sqft","rate_key":"package","inputs":[{"placeholder":"Area in sqft"}]},
  {"key":"sump","label":"RCC Water Sump","unit":"ltr","rate_key":"sump_rate","inputs":[{"placeholder":"No. of Liters"}]}
]
```

== Changelog ==
1.1.0
* Renamed to Construction Cost Calculator and added [construction_calculator] shortcode
* Data-driven packages and works, responsive UI, and JS calculations

1.0.0
* Initial release

