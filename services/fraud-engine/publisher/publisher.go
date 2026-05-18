package publisher

import (
	"context"
	"encoding/json"
	"fmt"

	amqp "github.com/rabbitmq/amqp091-go"
)

const (
	exchange   = "payment.events"
	routingKey = "payment.processed"
)

type PaymentProcessed struct {
	TransactionID string `json:"transaction_id"`
	CorrelationID string `json:"correlation_id"`
	Status        string `json:"status"`
	Reason        string `json:"reason"`
}

type Publisher struct {
	conn *amqp.Connection
}

func New(conn *amqp.Connection) *Publisher {
	return &Publisher{conn: conn}
}

func (p *Publisher) Publish(ctx context.Context, msg PaymentProcessed) error {
	ch, err := p.conn.Channel()
	if err != nil {
		return fmt.Errorf("open channel: %w", err)
	}
	defer ch.Close()

	body, err := json.Marshal(msg)
	if err != nil {
		return fmt.Errorf("marshal: %w", err)
	}

	return ch.PublishWithContext(ctx, exchange, routingKey, false, false, amqp.Publishing{
		ContentType:  "application/json",
		Body:         body,
		DeliveryMode: amqp.Persistent,
	})
}
