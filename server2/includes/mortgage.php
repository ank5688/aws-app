<?php
/**
 * Server 2: Mortgage Calculation Library
 * 
 * Calculates monthly payments and affordability based on:
 * - 20% down payment
 * - 30-year fixed mortgage
 * - Property tax (1.2% annually)
 * - Home insurance ($100/month)
 * - 28% affordability rule
 */

/**
 * Calculate monthly payment and affordability
 * 
 * @param float $price Home purchase price
 * @param float $annualRate Annual interest rate (e.g., 6.5 for 6.5%)
 * @param float $annualIncome Buyer's annual gross income
 * @return array Payment breakdown and affordability
 */
function calculateMonthlyPayment($price, $annualRate, $annualIncome) {
    // Constants
    $DOWN_PAYMENT_PERCENT = 0.20;  // 20%
    $LOAN_TERM_YEARS = 30;          // 30 years
    $PROPERTY_TAX_RATE = 0.012;     // 1.2% annually
    $INSURANCE_MONTHLY = 100;       // $100/month
    $AFFORDABILITY_RATIO = 0.28;    // 28% of gross monthly income
    
    // Calculate down payment and loan amount
    $downPayment = $price * $DOWN_PAYMENT_PERCENT;
    $loanAmount = $price - $downPayment;
    
    // Calculate monthly principal & interest
    $monthlyRate = ($annualRate / 100) / 12;
    $numPayments = $LOAN_TERM_YEARS * 12;
    
    // Monthly payment formula: M = P[r(1+r)^n]/[(1+r)^n-1]
    if ($monthlyRate > 0) {
        $monthlyPI = $loanAmount * 
                     ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
                     (pow(1 + $monthlyRate, $numPayments) - 1);
    } else {
        // If interest rate is 0, just divide by number of payments
        $monthlyPI = $loanAmount / $numPayments;
    }
    
    // Calculate monthly property tax
    $monthlyTax = ($price * $PROPERTY_TAX_RATE) / 12;
    
    // Total monthly payment
    $totalMonthly = $monthlyPI + $monthlyTax + $INSURANCE_MONTHLY;
    
    // Calculate maximum affordable payment (28% of monthly gross income)
    $monthlyIncome = $annualIncome / 12;
    $maxAffordable = $monthlyIncome * $AFFORDABILITY_RATIO;
    
    // Determine affordability
    $isAffordable = $totalMonthly <= $maxAffordable;
    
    return [
        'down_payment' => round($downPayment, 2),
        'loan_amount' => round($loanAmount, 2),
        'monthly_pi' => round($monthlyPI, 2),
        'monthly_tax' => round($monthlyTax, 2),
        'monthly_insurance' => $INSURANCE_MONTHLY,
        'monthly_payment' => round($totalMonthly, 2),
        'max_affordable' => round($maxAffordable, 2),
        'is_affordable' => $isAffordable,
        'affordability_ratio' => round(($totalMonthly / $monthlyIncome) * 100, 1)
    ];
}

/**
 * Calculate maximum home price based on income
 * 
 * @param float $annualIncome Annual gross income
 * @param float $annualRate Annual interest rate
 * @return float Maximum affordable home price
 */
function calculateMaxPrice($annualIncome, $annualRate) {
    $AFFORDABILITY_RATIO = 0.28;
    $DOWN_PAYMENT_PERCENT = 0.20;
    $LOAN_TERM_YEARS = 30;
    $PROPERTY_TAX_RATE = 0.012;
    $INSURANCE_MONTHLY = 100;
    
    $monthlyIncome = $annualIncome / 12;
    $maxPayment = $monthlyIncome * $AFFORDABILITY_RATIO;
    
    // Subtract insurance from available payment
    $availableForLoanAndTax = $maxPayment - $INSURANCE_MONTHLY;
    
    $monthlyRate = ($annualRate / 100) / 12;
    $numPayments = $LOAN_TERM_YEARS * 12;
    
    // This is an approximation - iterative calculation would be more precise
    if ($monthlyRate > 0) {
        $factor = ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
                  (pow(1 + $monthlyRate, $numPayments) - 1);
        
        // Approximate max loan considering tax
        $maxLoan = $availableForLoanAndTax / ($factor + ($PROPERTY_TAX_RATE / 12));
        
        // Convert to max price (loan is 80% of price)
        $maxPrice = $maxLoan / (1 - $DOWN_PAYMENT_PERCENT);
    } else {
        $maxPrice = ($availableForLoanAndTax * $numPayments) / (1 - $DOWN_PAYMENT_PERCENT);
    }
    
    return round($maxPrice, 2);
}

/**
 * Format payment details as human-readable text
 * 
 * @param array $payment Payment calculation result
 * @return string Formatted payment summary
 */
function formatPaymentSummary($payment) {
    $summary = "Monthly Payment Breakdown:\n";
    $summary .= "- Principal & Interest: $" . number_format($payment['monthly_pi'], 2) . "\n";
    $summary .= "- Property Tax: $" . number_format($payment['monthly_tax'], 2) . "\n";
    $summary .= "- Insurance: $" . number_format($payment['monthly_insurance'], 2) . "\n";
    $summary .= "- Total: $" . number_format($payment['monthly_payment'], 2) . "\n";
    $summary .= "\nAffordability: " . ($payment['is_affordable'] ? "Yes" : "No");
    $summary .= " (Payment is " . $payment['affordability_ratio'] . "% of income)";
    
    return $summary;
}
?>
