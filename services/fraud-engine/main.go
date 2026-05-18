package main

import (
	"context"
	"fmt"
	"log"
	"os"
	"time"

	"github.com/gofiber/fiber/v3"
	amqp "github.com/rabbitmq/amqp091-go"

	"fraud-engine/consumer"
	"fraud-engine/handler"
	"fraud-engine/publisher"
)

func connectRabbitMQ(url string) (*amqp.Connection, error) {
	var err error
	for i := range 5 {
		conn, dialErr := amqp.Dial(url)
		if dialErr == nil {
			return conn, nil
		}
		err = dialErr
		wait := time.Duration(i+1) * 2 * time.Second
		log.Printf("rabbitmq not ready, retrying in %s: %v", wait, err)
		time.Sleep(wait)
	}
	return nil, fmt.Errorf("failed to connect after retries: %w", err)
}

func main() {
	rabbitURL := os.Getenv("RABBITMQ_URL")
	if rabbitURL == "" {
		rabbitURL = "amqp://guest:guest@localhost:5672/"
	}

	conn, err := connectRabbitMQ(rabbitURL)
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