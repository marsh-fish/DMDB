# DMDB

DMDB is a document management website that runs in a local LAMP environment using Docker Compose. This project is based on the [sprintcube/docker-compose-lamp](https://github.com/sprintcube/docker-compose-lamp) setup.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

Make sure you have Docker and Docker Compose installed on your system. You can download them from:
- [Docker](https://www.docker.com/products/docker-desktop)
- [Docker Compose](https://docs.docker.com/compose/install/)

### First Time Setup

To set up the project for the first time, follow these steps:

1. Build the Docker containers:
   ```bash
   make build
   ```

2. Start the Docker containers:
   ```bash
   make run
   ```

3. Modify the permissions for the uploads directory to allow file uploading:
   ```bash
   chmod 777 www/uploads/
   ```

4. Set up the database:
   - Open phpMyAdmin at [http://localhost:8080/](http://localhost:8080/)
   - Click on the "docker" database
   - Go to "Import"
   - Choose the file located at `sql/docker.sql`

After these steps, the site will be available at [http://localhost/login.php](http://localhost/login.php)

### Running the Application

To start the application, use the following command:
```bash
make run
```

To stop the application, use the following command:
```bash
make stop
```

To view the current status of the servers, use:
```bash
make status
```

### Environment Variables

All configuration options are stored in the `.env` file. To make changes:
- Edit the `simple.env` file
- Apply the changes by rebuilding the containers:
  ```bash
  make build
  ```

### Database Initialization

To reinitialize the database:
- Open phpMyAdmin and select the "docker" database
- Drop all tables within the database
- Re-import the `sql/docker.sql` file as previously described

## Authors

* **marsh-fish** - *Initial work* - [marsh-fish](https://github.com/marsh-fish)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
