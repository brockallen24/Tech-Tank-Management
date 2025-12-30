# Tech Tank Management Programs

Professional management dashboard for inventory, manufacturing, and job tracking.

## Features

- **Inventory Management**: Track stock levels, suppliers, and transactions
- **Manufacturing Schedule**: Manage production schedules and maintenance
- **Job Tracking**: Monitor employee time and part counting

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: Node.js, Express
- **Database**: Airtable
- **Hosting**: Heroku
- **Version Control**: GitHub

## Setup Instructions

### 1. Install Dependencies
```bash
npm install
```

### 2. Configure Environment Variables
Create a `.env` file with your Airtable credentials:
```
AIRTABLE_API_KEY=your_api_key
AIRTABLE_INVENTORY_BASE_ID=your_base_id
AIRTABLE_MANUFACTURING_BASE_ID=your_base_id
AIRTABLE_JOBTRACKING_BASE_ID=your_base_id
```

### 3. Run Locally
```bash
npm start
```

Visit http://localhost:3000

### 4. Deploy to Heroku
```bash
git push heroku main
```

## File Structure
```
tech-tank-management/
├── public/
│   ├── index.html (home screen)
│   └── inventory_custom_layout.html
├── server.js
├── package.json
├── Procfile
├── .env (not in git)
└── README.md
```

## Support

For issues or questions, contact Tech Tank support.

© 2024 Tech Tank Management Programs
