package handler

import (
	"context"
	"log"

	"fraud-engine/consumer"
	"fraud-engine/fraud"
	"fraud-engine/publisher"
)

type PaymentHandler struct {
	pub *publisher.Publisher
}

func NewPaymentHandler(pub *publisher.Publisher) *PaymentHandler {
	return &PaymentHandler{pub: pub}
}

func (h *PaymentHandler) Handle(ctx context.Context, msg consumer.PaymentInitiated) error {
	result := fraud.Check(msg.Amount, msg.Currency, msg.PaymentMethod)

	status := "approved"
	if !result.Approved {
		status = "rejected"
	}

	log.Printf("handler: transaction %s -> %s flags=%v reason=%q", msg.TransactionID, status, result.Flags, result.Reason)

	return h.pub.Publish(ctx, publisher.PaymentProcessed{
		TransactionID: msg.TransactionID,
		CorrelationID: msg.CorrelationID,
		Status:        status,
		Reason:        result.Reason,
	})
}
