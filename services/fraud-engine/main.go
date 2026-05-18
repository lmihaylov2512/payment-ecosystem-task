package main

import (
	"context"
	"log"
	"os"

	"github.com/gofiber/fiber/v3"
	amqp "github.com/rabbitmq/amqp091-go"

	"fraud-engine/consumer"
	"fraud-engine/handler"
	"fraud-engine/publisher"
)

func main() {
	rabbitURL := os.Getenv("RABBITMQ_URL")
	if rabbitURL == "" {
		rabbitURL = "amqp://guest:guest@localhost:5672/"
	}

	conn, err := amqp.Dial(rabbitURL)
	if err != nil {
		log.Fatalf("rabbitmq connect: %v", err)
	}
	defer conn.Close()

	pub := publisher.New(conn)
	h := handler.NewPaymentHandler(pub)
	c := consumer.New(conn, h)

	ctx := context.Background()
	go func() {
		if err := c.Start(ctx); err != nil {
			log.Fatalf("consumer: %v", err)
		}
	}()

	app := fiber.New()
	app.Get("/health", func(c fiber.Ctx) error {
		return c.JSON(fiber.Map{"status": "ok"})
	})

	log.Fatal(app.Listen(":3000"))
}
