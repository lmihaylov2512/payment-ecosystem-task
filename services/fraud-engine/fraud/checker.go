package fraud

type Result struct {
	Approved bool
	Flags    []string
	Reason   string
}

func Check(amount float64, currency, paymentMethod string) Result {
	var flags []string

	if amount > 1000 {
		flags = append(flags, "high_risk")
	}

	if len(flags) > 0 {
		return Result{
			Approved: false,
			Flags:    flags,
			Reason:   flags[0],
		}
	}

	return Result{Approved: true, Flags: flags}
}
