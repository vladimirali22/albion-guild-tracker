⚔️ Albion Guild Tracker & Tools
A High-End Profit Tracking & Automation Suite inspired by Albion Online.

Designed for traders, crafters, and guild leaders who want to dominate the market.

Status: 🛠️ Active Project - New tools are added regularly!

🔥 Featured Tool: Albion Flip Calculator
The most accurate flipping tool that takes the guesswork out of market taxes.

Features:
Smart Tax Logic: Automatically calculates 2.5% Setup Fees and Transaction Taxes.

Premium Toggle: Switch between 4% (Premium) and 8% (Non-Premium) taxes with one click.

Profit Projections: See your net profit for x1, x10, and x100 quantities instantly.

Break-Even Analysis: Know exactly the minimum price to sell without losing silver.

ROI Tracking: Visual percentage indicators to help you pick the best trades.

🚀 Quick Setup (XAMPP)
1. Copy Files
Place the entire albion-guild-tracker/ folder inside:

C:\xampp\htdocs\albion-guild-tracker\
2. Start XAMPP
Start Apache and MySQL from the XAMPP Control Panel.

3. Database Setup
Open http://localhost/phpmyadmin

Create a database named albion_guild_tracker.

Import the sql/database.sql file provided in this repository.

4. Open the App
http://localhost/albion-guild-tracker/flip.php
📁 Project Structure
albion-guild-tracker/
├── flip.php            → ⚖️ NEW: Market Flip Calculator
├── api/
│   └── save_trade.php  → 🔐 Database handler for trades
├── dashboard.php       → 🛡️ Warrior dashboard (protected)
├── config.php          → ⚙️ DB connection & global settings
├── sql/
│   └── database.sql    → 🗄️ Database schema
├── assets/
│   ├── css/            → Dark-fantasy theme styles
│   └── js/
│       ├── flip.js     → 🧠 The math engine behind the calculator
│       └── script.js   → UI animations & sound FX
└── README.md
🛠️ Upcoming Tools (Roadmap)
I am constantly developing new tools for the community. Stay tuned for:

[ ] Crafting Profit Calculator (including Focus cost).

[ ] Island Yield Tracker.

[ ] Guild Tax & Donation System.

[ ] Black Market Flip Analyzer.

📺 Video Tutorial & Support
Need help or want to see this tool in action?

Check out my YouTube channel: [Alt F4 Guide] (link_here)

I explain how to use this tool to make millions of silver daily.

🔐 Security & Tech Stack
Backend: PHP 8.x + MySQL (PDO).

Frontend: Vanilla JS, CSS3 (Dark Fantasy Theme).

Security: Password hashing (Bcrypt) & SQL Injection protection.

May your silver grow and your fame never falter, warrior. ⚔️
Developed by [vladimirali22]
