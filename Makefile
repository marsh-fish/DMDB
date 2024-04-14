include sample.env
export

# 定義命令
.PHONY: build run stop

all: build

build:
	@cp sample.env .env
	@docker-compose build

run:
	@docker-compose up -d
	@echo "browse web on http://localhost:$(HOST_MACHINE_UNSECURE_HOST_PORT)"

status:
	@echo "Checking status of all services..."
	@docker-compose ps
	@docker-compose top

stop:
	@docker-compose down
