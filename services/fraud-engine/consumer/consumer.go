package consumer

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
)

const (
	exchange   = "payment.events"
	queue      = "fraud-engine.payment-initiated"
	routingKey = "payment.initiated"

	maxRetries   = 5
	initialDelay = time.Second
	maxDelay     = 32 * time.Second
)

type PaymentInitiated struct {
	TransactionID string  `json:"transaction_id"`
	CorrelationID string  `json:"correlation_id"`
	UserID        int64   `json:"user_id"`
	Amount        float64 `json:"amount"`
	Currency      string  `json:"currency"`
	PaymentMethod string  `json:"payment_method"`
}

type Handler interface {
	Handle(ctx context.Context, msg PaymentInitiated) error
}

type Consumer struct {
	conn    *amqp.Connection
	handler Handler
}

func New(conn *amqp.Connection, handler Handler) *Consumer {
	return &Consumer{conn: conn, handler: handler}
}

func (c *Consumer) Start(ctx context.Context) error {
	ch, err := c.conn.Channel()
	if err != nil {
		return fmt.Errorf("open channel: %w", err)
	}
	defer ch.Close()

	if err := ch.ExchangeDeclare(exchange, "topic", true, false, false, false, nil); err != nil {
		return fmt.Errorf("declare exchange: %w", err)
	}

	q, err := ch.QueueDeclare(queue, true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("declare queue: %w", err)
	}

	if err := ch.QueueBind(q.Name, routingKey, exchange, false, nil); err != nil {
		return fmt.Errorf("bind queue: %w", err)
	}

	msgs, err := ch.Consume(q.Name, "", false, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("start consuming: %w", err)
	}

	log.Println("consumer: listening on", queue)

	for {
		select {
		case <-ctx.Done():
			return nil
		case msg, ok := <-msgs:
			if !ok {
				return fmt.Errorf("consumer: channel closed")
			}
			if err := c.processWithRetry(ctx, msg); err != nil {
				log.Printf("consumer: exhausted retries, discarding message: %v", err)
				msg.Nack(false, false)
				continue
			}
			msg.Ack(false)
		}
	}
}

func (c *Consumer) processWithRetry(ctx context.Context, msg amqp.Delivery) error {
	delay := initialDelay
	for attempt := range maxRetries {
		err := c.process(ctx, msg)
		if err == nil {
			return nil
		}
		if attempt == maxRetries-1 {
			return fmt.Errorf("attempt %d: %w", attempt+1, err)
		}
		log.Printf("consumer: attempt %d/%d failed, retrying in %s: %v", attempt+1, maxRetries, delay, err)
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(delay):
		}
		delay = min(delay*2, maxDelay)
	}
	return nil
}

func (c *Consumer) process(ctx context.Context, msg amqp.Delivery) error {
	var payment PaymentInitiated
	if err := json.Unmarshal(msg.Body, &payment); err != nil {
		return fmt.Errorf("unmarshal: %w", err)
	}
	return c.handler.Handle(ctx, payment)
}