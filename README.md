# UCRM Holidays plugin

Changes invoice due date if it is on a day off (holiday, weekend)

## Installation

1. Clone this repository: `git clone https://github.com/jacekkow/ucrm-holidays`
2. Run composer: `composer update`
3. Pack the plugin: `./vendor/bin/pack-plugin`
4. Upload holidays.zip file in UCRM System -> Plugins tab.
5. Click "Add webhook" button next to the "Public URL" field.
6. Configure region and weekdays to skip.
