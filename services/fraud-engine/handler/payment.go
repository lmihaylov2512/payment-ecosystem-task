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

	log.Printf("handler: transaction %s high_risk=%v flags=%v", msg.TransactionID, !result.Approved, result.Flags)

	return h.pub.Publish(ctx, publisher.PaymentProcessed{
		TransactionID: msg.TransactionID,
		CorrelationID: msg.CorrelationID,
		Amount:        msg.Amount,
		HighRisk:      !result.Approved,
	})
}
