<?php

/*
| The public pages beyond home: the topic pages people search for, the
| pricing page and the guides. Every claim here is true of the product
| today; prices are never written here, they come from the packages an
| admin sets (App\Services\Portal\PricingCards).
*/

return [

    'nav' => [
        'lebanon' => 'QR menu in Lebanon',
        'cafes' => 'Digital menu for cafés',
        'pricing' => 'Pricing',
        'guides' => 'Guides',
    ],

    'cta' => [
        'title' => 'Put your menu on every phone today.',
        'body' => 'Start on the Free package. No card, no app for your guests, and one QR code that keeps working whatever you change.',
        'button' => 'Get started free',
        'secondary' => 'See pricing',
    ],

    'faq_title' => 'Questions',

    'related' => 'Read next',

    'lebanon' => [
        'seo' => [
            'title' => 'QR Menu for Restaurants in Lebanon',
            'description' => 'A QR code menu for restaurants and cafés in Lebanon: English and Arabic, prices in dollars or Lebanese pounds, orders on WhatsApp. Free to start.',
        ],
        'eyebrow' => 'Lebanon',
        'title' => 'A QR menu made for',
        'title_gold' => 'restaurants in Lebanon.',
        'intro' => 'Qayema is a digital menu built in Lebanon. Your guests scan one QR code and read your menu in English or Arabic, with prices in the currency you work in, and you change anything from your phone in seconds.',
        'sections' => [
            [
                'title' => 'English and Arabic on one menu',
                'body' => [
                    'Every Qayema menu is written in English. With the Pro or Premium package you add a second language, and for most places in Lebanon that is Arabic. Guests switch with one tap, and the Arabic version reads right to left with Arabic fonts.',
                    'If your guests read French more than Arabic, choose French instead. You can pick one second language from ten, and change it later.',
                ],
            ],
            [
                'title' => 'Prices in dollars or Lebanese pounds',
                'body' => [
                    'You set your menu\'s currency when you sign up, and every price shows in it: US dollars, Lebanese pounds or another currency. When prices move, you update them from your dashboard and the menu on every table changes at once.',
                ],
                'points' => [
                    'Change a price and it is live in seconds.',
                    'Mark a dish sold out with one tap.',
                    'The QR code on your tables never changes, so there is nothing to reprint.',
                ],
            ],
            [
                'title' => 'Orders straight to your WhatsApp',
                'body' => [
                    'With the Premium package, guests add dishes to a cart and send the order to your WhatsApp in one message, with the dishes, quantities and total. Every order also lands in your dashboard, where you mark it done.',
                ],
            ],
            [
                'title' => 'Your hours, location and links in one place',
                'body' => [
                    'Your menu shows when you are open, your phone number, a map to find you and your social links. Guests do not download anything: the menu opens in their phone\'s browser.',
                ],
            ],
        ],
        'faq' => [
            [
                'q' => 'Can my menu be in Arabic only?',
                'a' => 'Every menu has English as its first language, and Arabic can be the second. With Pro or Premium, you can choose to open the menu in Arabic, so guests see Arabic first.',
            ],
            [
                'q' => 'Can I show prices in Lebanese pounds?',
                'a' => 'Yes. Choose Lebanese pounds as your menu\'s currency and every price shows in it. One currency is used per menu.',
            ],
            [
                'q' => 'How do Lebanese guests order?',
                'a' => 'With Premium, they fill a cart on your menu and send it to your WhatsApp, which most of them already use every day.',
            ],
            [
                'q' => 'How do I pay for a package?',
                'a' => 'There is no card and no checkout. Ask for Pro or Premium from your dashboard and we contact you to agree the details.',
            ],
        ],
    ],

    'cafes' => [
        'seo' => [
            'title' => 'Digital Menu for Cafés',
            'description' => 'A digital QR menu for your café: drinks and food with photos, sold out in one tap, your own colours, and orders sent to WhatsApp. Free to start.',
        ],
        'eyebrow' => 'Cafés',
        'title' => 'A digital menu for',
        'title_gold' => 'your café.',
        'intro' => 'A café menu changes all the time: a new drink, a seasonal cake, an oat milk that ran out. With Qayema your menu lives online, guests open it with a QR code on the table, and you keep it right from your phone.',
        'sections' => [
            [
                'title' => 'Built for a menu that changes often',
                'body' => [
                    'Group your menu into categories such as hot drinks, cold drinks, pastries and breakfast, then add each item with its price, what is in it and a photo. Drag items to put the best sellers first.',
                ],
                'points' => [
                    'Ran out of something? Mark it sold out in one tap, and it leaves the menu until you bring it back.',
                    'A new seasonal drink goes live the moment you save it.',
                    'Your QR code stays the same, so table cards and stickers never need reprinting.',
                ],
            ],
            [
                'title' => 'Looks like your café',
                'body' => [
                    'Choose a design for your menu, then make it yours with your own colours and fonts, your logo and a cover photo. With the QR studio you can also style the code itself and print a table card with your colours.',
                ],
            ],
            [
                'title' => 'Orders without a queue',
                'body' => [
                    'With Premium, guests choose what they want on their phone and send the order to your WhatsApp, so the counter is free for the people waiting.',
                ],
            ],
            [
                'title' => 'See what guests look at',
                'body' => [
                    'Analytics show how many people opened your menu and how many came through the QR code, day by day. Advanced analytics add your busiest hours, what guests search for and the items they add to their cart.',
                ],
            ],
        ],
        'faq' => [
            [
                'q' => 'Do guests need an app?',
                'a' => 'No. They scan the QR code and the menu opens in their browser, on any phone.',
            ],
            [
                'q' => 'Can I add photos of drinks and food?',
                'a' => 'Yes. Every item can have a photo, which we resize so the menu stays quick to load.',
            ],
            [
                'q' => 'How many items can I add?',
                'a' => 'Each package has its own limits, shown on the pricing page. The Free package is enough to start a small café menu.',
            ],
        ],
    ],

    'pricing' => [
        'seo' => [
            'title' => 'Pricing',
            'description' => 'Qayema packages and prices: start on Free, move up to Pro or Premium when you need more, or talk to us about Custom for a group of restaurants.',
        ],
        'eyebrow' => 'Pricing',
        'title' => 'Simple packages,',
        'title_gold' => 'no checkout.',
        'intro' => 'Start on Free and keep it for as long as you like. When you want a second language, your own colours, the QR studio or orders on WhatsApp, ask for a package from your dashboard.',
        'faq' => [
            [
                'q' => 'Is the Free package really free?',
                'a' => 'Yes. It has no end date and needs no card. You build and publish your menu at no cost.',
            ],
            [
                'q' => 'How do I move to Pro or Premium?',
                'a' => 'Open the Package page in your dashboard, choose the package and send your request. We contact you to agree the price and payment, then switch it on.',
            ],
            [
                'q' => 'What happens if my package ends?',
                'a' => 'Your menu goes back to Free and nothing is deleted. Your settings wait for you if you move up again.',
            ],
            [
                'q' => 'What does "Unlimited" mean?',
                'a' => 'Where a package says unlimited dishes or categories, a fair-use limit applies: the number is under the packages above, far more than a restaurant menu needs. If you ever reach it, contact us.',
            ],
            [
                'q' => 'Do you have a package for several restaurants?',
                'a' => 'Yes, Custom. Tell us what your group needs and we build the package around it.',
            ],
        ],
    ],

    'guides' => [
        'seo' => [
            'title' => 'Guides for Restaurant Owners',
            'description' => 'Practical guides on QR code menus: how to make one, what it costs compared with paper menus, and how to keep your prices up to date.',
        ],
        'eyebrow' => 'Guides',
        'title' => 'Guides for',
        'title_gold' => 'restaurant owners.',
        'intro' => 'Short, practical reads on running a digital menu.',
        'read' => 'Read the guide',
        'minutes' => ':count min read',
        'updated' => 'Updated :date',
        'all' => 'All guides',
        'crumb' => 'Guides',
    ],

    'articles' => [

        'how-to-make-a-qr-menu' => [
            'title' => 'How to Make a QR Code Menu for Your Restaurant',
            'description' => 'A step by step guide to making a QR code menu for your restaurant or café: build the menu, print the code, and keep it up to date.',
            'summary' => 'Build the menu, print the code, keep it fresh: the whole process, step by step.',
            'published' => '2026-10-01',
            'minutes' => 5,
            'sections' => [
                [
                    'title' => 'What a QR code menu is',
                    'body' => [
                        'A QR code menu is your menu as a web page. Guests point their phone camera at a code on the table, and the menu opens in their browser. Nothing to download, and nothing for you to reprint when something changes, because the code always opens the latest version.',
                    ],
                ],
                [
                    'title' => 'Step 1: Gather your menu',
                    'body' => [
                        'Start from the menu you have today. Write down your categories in the order guests should see them, then every dish with its price and a short line on what is in it. Photos help guests choose, so take clear photos of your best sellers in good light.',
                    ],
                ],
                [
                    'title' => 'Step 2: Create your menu online',
                    'body' => [
                        'With Qayema, sign up with your Google account, give your restaurant a name and a link, choose your currency and add your logo. Then add your categories and dishes from the dashboard. Drag them into order, and add a second language if your guests need one.',
                    ],
                    'points' => [
                        'Keep dish names short and clear.',
                        'Put ingredients and allergens in the description.',
                        'Put your best sellers first in each category.',
                    ],
                ],
                [
                    'title' => 'Step 3: Make it look like your place',
                    'body' => [
                        'Pick a design, then set your colours, fonts and a cover photo so the menu feels like your restaurant, not a generic page. Check it on your own phone the way a guest would see it.',
                    ],
                ],
                [
                    'title' => 'Step 4: Print your QR code',
                    'body' => [
                        'Download your QR code as a PNG for everyday printing or as an SVG for a large print that stays sharp. Put it where every guest can see it: on each table, at the entrance and at the counter. Make it big enough to scan from a seat, and test it with a few phones before you print many.',
                    ],
                ],
                [
                    'title' => 'Step 5: Keep it up to date',
                    'body' => [
                        'This is where a QR menu pays off. Change a price, add a dish or mark one sold out from your dashboard, and it is live at once. The code on your tables keeps opening the new menu, so you print it only once.',
                    ],
                ],
            ],
        ],

        'qr-menu-vs-paper-menu-cost' => [
            'title' => 'QR Menu vs Paper Menu: What Does Each Really Cost?',
            'description' => 'Compare what a paper menu and a QR code menu cost your restaurant over a year, with a simple way to work out your own numbers.',
            'summary' => 'A simple way to work out what paper menus cost you over a year, and how a QR menu compares.',
            'published' => '2026-10-01',
            'minutes' => 4,
            'sections' => [
                [
                    'title' => 'The cost of paper is not the first print',
                    'body' => [
                        'Printing a set of menus once does not cost much. The cost comes from printing them again: every new price, new dish, removed dish and typo means a new print run, and menus that get worn or stained need replacing too.',
                    ],
                ],
                [
                    'title' => 'Work out your own paper menu cost',
                    'body' => [
                        'Take your own numbers, because they differ for every restaurant. Multiply them out for one year:',
                    ],
                    'points' => [
                        'How many menus you keep in use.',
                        'What one menu costs to print, laminate or bind.',
                        'How many times a year you reprint because something changed.',
                        'How many worn menus you replace between reprints.',
                    ],
                    'after' => [
                        'Menus in use × cost per menu × reprints per year, plus replacements, is what paper costs you each year. If your prices change often, the reprints count is the number that grows.',
                    ],
                ],
                [
                    'title' => 'The hidden costs',
                    'body' => [
                        'Some costs never show on a printer\'s invoice. When reprinting is a hassle, menus stay out of date: crossed out prices, dishes you no longer serve, and guests who order something that is not available. Staff spend time explaining what changed.',
                    ],
                ],
                [
                    'title' => 'What a QR menu costs',
                    'body' => [
                        'With a QR menu you print the code once: a sticker or a table card for each table. After that, changes cost nothing to make, because you edit the menu online and the same code opens it. With Qayema you can start on the Free package, and paid packages add more when you need it.',
                    ],
                ],
                [
                    'title' => 'Which is right for you',
                    'body' => [
                        'If your menu rarely changes and your guests prefer paper, a few printed menus may be enough. If your prices change often, you add dishes through the season or you serve guests in two languages, a QR menu saves you the reprints and keeps every table up to date. Many places keep a few paper menus for guests who ask, next to a QR code on every table.',
                    ],
                ],
            ],
        ],

        'update-menu-prices-fast' => [
            'title' => 'How to Update Your Menu Prices Fast',
            'description' => 'How to update your restaurant menu prices in seconds, keep every table up to date and avoid reprinting, with a digital QR code menu.',
            'summary' => 'Change a price in seconds and have it live on every table, without reprinting anything.',
            'published' => '2026-10-01',
            'minutes' => 3,
            'sections' => [
                [
                    'title' => 'Why prices fall behind',
                    'body' => [
                        'When your supplier\'s prices move, your menu has to follow. On paper that means a reprint or a pen, so updates get put off, and the menu on the table no longer matches the bill. That costs you either money or your guests\' trust.',
                    ],
                ],
                [
                    'title' => 'Update a price in seconds',
                    'body' => [
                        'With a digital menu, a price is just a field you edit. In Qayema, open your dishes, change the price and save. The menu every guest opens shows the new price straight away.',
                    ],
                    'points' => [
                        'Open your dashboard on your phone or computer.',
                        'Find the dish and change its price.',
                        'Save. Every table now shows the new price.',
                    ],
                ],
                [
                    'title' => 'Mark a dish sold out instead of removing it',
                    'body' => [
                        'If something runs out, do not delete it. Mark it sold out with one tap, and it leaves the menu until you bring it back, with its photo and description kept for next time.',
                    ],
                ],
                [
                    'title' => 'Keep the same QR code',
                    'body' => [
                        'Because the code on your tables opens your menu online, it never changes when your prices do. Print it once, and every update you make reaches every table.',
                    ],
                ],
                [
                    'title' => 'A routine that works',
                    'body' => [
                        'Pick a moment each week, or each time a supplier changes a price, to check your menu. Fix prices, mark what has run out and add what is new. It takes minutes, and your menu is never out of date.',
                    ],
                ],
            ],
        ],

    ],

];
