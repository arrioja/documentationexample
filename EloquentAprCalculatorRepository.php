<?php

namespace App\Repositories\AprCalculator;

/** USE SECTION */

use App\Exceptions\AprException;
use DateTime;
use App\Repositories\PayFrequency\PayfrequencyRepository;
use App\AmortizationSchedule;
use App\AmortizationScheduleDetails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;


class EloquentAprCalculatorRepository implements AprCalculatorRepository
{

    /*********************************************************************************************
     * ***************************   GLOBAL VARIABLES AND CONSTANTS   ****************************
     *********************************************************************************************/

    private $frequencyRepo; // This is used to validate the dates of the re-payments

    private const LOANYEARS = 2; // This is the amount of years a loan is supposed to last. Per client requierment this will never change.

    // These are the possible number of periods per year, depending on the payment frequency
    public $allFrequencies = [
        ['id'=>'DA','periods'=>365], // Days in a year
        ['id'=>'WE','periods'=>52], // Weekly
        ['id'=>'BW','periods'=>26],  // Bi-weekly
        ['id'=>'SM','periods'=>24],  // Semi-Monthly and bi-monthly is similar
        ['id'=>'MO','periods'=>12],  // Monthly
        ['id'=>'BM','periods'=>6],  // Bi-Monthly (Every 2 months) -> twice a month
    ];


    /*********************************************************************************************
     * *******************************************************************************************
     * ********************************** VALIDATION FUNCTIONS  **********************************
     * *******************************************************************************************
     *********************************************************************************************/

    /**
     * @param PayfrequencyRepository $frequencyRepo
     */
    public function __construct(PayfrequencyRepository $frequencyRepo)
    {
        $this->frequencyRepo = $frequencyRepo;
    }




    /**
     * This function check if a given date is a valid date
     *
     * @param string $date -> the date to be checked
     * @return bool -> True if it's a valid date, False if it's an invalid date
     */

    public function isValidDate ($date = ''){
        // Decompose the date and run it through "checkdate" function.
        return checkdate((int)date("m", strtotime($date)),
            (int)date("d", strtotime($date)),
            (int)date("Y", strtotime($date)));
    }




    /**
     * This function moves the date that was passed to it to a date that is not within a weekend.
     * Depending on the direction, it will move forward (+) to Monday or backwards (-) to Friday
     *
     * *******************************************************************************************
     * NOTE: THIS FUNCTION IS CURRENTLY NOT IN USE BECAUSE WE ARE USING THE IMPLEMENTATION OF
     * applyPayrollFrequencyRules FUNCTION THAT ALSO MAKES SURE WE SKIP NOT ONLY WEEKENDS BUT
     * HOLIDAYS AS WELL
     * *******************************************************************************************
     *
     * @param $date -> This is the date we want to move OUT form the weekend it is.
     * @param string $direction -> direction to move OUT from the weekend. - =Backwards, + =Forward
     * @return mixed -> The resulting date will be the closest workday (non-weekend), depending on the direction
     *      If it's forward, the next date will be a Monday, If It's backwards, the closest would be a Friday
     *      If the date passed as a parameter is already a business day, it won't me changed.
     *      IMPORTANT: This only considers weekends, any holiday is not taken into consideration
     * @throws AprException
     */

    public function moveAwayFromWeekend($date, $direction = '+')
    {
        /** Validations **/
        // We limit the value of the direction to what's defined
        if(!in_array($direction, array("+", "-")))
        { $direction = '+';}

        // Check that the date is valid date
        if( $this->isValidDate($date) == false)
        { throw new AprException(AprException::ERR_Invalid_Date);}

        /** Procedure **/
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }





    /**
     * This function calculates the number of days between 2 dates
     * It includes validation and returns an integer
     *
     * @param $earlierDate -> First, earlier date
     * @param $laterDate -> Second, later date
     * @return integer -> returns the number of days between $earlierDate and $laterDate
     * @throws AprException
     */
    public function dateDiff ($earlierDate, $laterDate){

        /** Validation */
        // Check that the dates are valid
        if (($this->isValidDate($earlierDate) == false) || ( $this->isValidDate($laterDate) == false) ||
            ($earlierDate > $laterDate)){
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        /** Procedure */

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }




    /**
     * This function returns the number of periods, depending on the payment frequency
     *
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class, by default it's "BW" Biweekly
     * @param string $type -> 'Y' for the number of period in the year. 'T' for the total number
     * @return integer -> the number of periods for the frequency, being in a year or in total
     * @throws AprException
     */
    public function getPeriods($paymentFrequency='BW', $type = 'Y')
    {

        /** Validations **/
        // Payment frequency must be a valid one
        if ($this->isValidFrequency($paymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        // In case it's different from the allowed options, we force the default
        if(!in_array($type, array('Y', 'T')))
        { $type = 'Y';}

        /** Procedure **/
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

        return $periods;
    }




    /**
     * This function generates a list of dates based on the initial date provided and the frequency
     * of the payments.
     *
     * @param string $initialDate -> the date that will be used to start counting (this date included), and
     *      must be in the format: yyyy/mm/dd
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class, by default it's "BW" Biweekly
     *   Possible values are defined in the public variable $allFrequencies at the beginning of this class
     * @param bool $skip -> whether to skip weekends and holidays or not. (false = Don't skip, true = skip)
     * @param null $endDate -> If provided, this will the end date to calc the schedule, if not provided, a 2 year default is in place
     * @return array
     * @throws AprException Additional note: This function uses a function from PayFrequencyRepository that allows to move the current date
     * forward or backward in tim depending if it's closer to the Friday or to the monday.
     * This is with the intention of skipping the weekend and any holiday registered.
     */
    public function calcDates($initialDate='', $paymentFrequency='BW', $skip = false, $endDate = null)
    {

        /** Validations */

        // Check that the date is valid date
        if( $this->isValidDate($initialDate) == false)
        {
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        // Payment frequency must be a valid one
        if ($this->isValidFrequency($paymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        // This is the number of payments or periods to pay the loan in full
        $periods = $this->getPeriods($paymentFrequency,'T');

        /*  According to email instructions, there is a fixed hard limit for the end date of any loans which is 2 years
            that's the reason why endDate variable is +2 Years */
        if (($endDate == null) || (!isset($endDate))){
            $endDate = strtotime('+ 2 year', strtotime($initialDate));
        }

        $date = $initialDate;
        $response = array();

        // We enter the first date to be the date we granted the loan
        array_push($response,$date);

        /** Procedure */

 // THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
        return $response;
    }




    /**
     * Validates if the Payment Frequency is within the allowed options.
     *
     * @param $payFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class.
     * @return bool
     */
    public function isValidFrequency($payFrequency)
    {
        /** Procedure **/
        return (array_search($payFrequency, array_column($this->allFrequencies, 'id')) == false) ? false : true;
    }




    /*********************************************************************************************
     * *******************************************************************************************
     * ********************************* CALC RELATED FUNCTIONS  *********************************
     * *******************************************************************************************
     *********************************************************************************************/




    /**
     * This function calculates the interest rate per period of the loan, depending on the payment frequency
     *
     * @param float $annualInterestRate -> This is the annual interest rate
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class, by default it's "BW" Biweekly
     *   Possible values are defined in the global variable $allFrequencies
     * @return float -> The interest rate per period expressed in decimal. 0.012 = 1.2%
     * @throws AprException
     *
     */

    public function calcInterestRatePerPeriod(float $annualInterestRate = 0.00, $paymentFrequency='BW')
    {
        /** Validations **/

        // Payment frequency must be a valid one
        if (($this->isValidFrequency($paymentFrequency) == false) || (!isset($paymentFrequency))){
            throw new AprException(AprException::ERR_Frequency);
        }

        // Periods must be a positive integer
        if ((!isset($annualInterestRate)) || (!is_float($annualInterestRate)) || ($annualInterestRate <=0)){
            throw new AprException(AprException::ERR_Annual_Interest_Rate);
        }

        /** Procedure **/
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }




    /**
     * This function calculates the amount of interest earned (in USD) in the given number of days
     *
     * @param $days -> Number of days that passed
     * @param $principal -> The principal amount to calc the interests from
     * @param $annualInterestRate -> Expressed as a decimal, e.g. 0.2495 = 24.95%/year
     * @return float|int -> The interest in USD generated in the given number of days
     *
     * @throws AprException
     */

    public function calcInterest ($days, $principal, $annualInterestRate){

        /** Validations **/
        if ($annualInterestRate < 0) {
            throw new AprException(AprException::ERR_Daily_Interest_Rate);
        }
        if ($days < 0) {
            throw new AprException(AprException::ERR_Days);
        }
        if (round($principal,2) < 0) {
            throw new AprException(AprException::ERR_Principal);
        }

        /** Procedure **/

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }




    /**
     * This function calculates the first payment as an approximation for the amortization table
     *
     * @param int $loanAmount -> Loan amount, this must include any fees that adds to the total amount of the loan
     * @param float $annualInterestRate -> Expressed as a decimal, e.g. 0.2495 = 24.95%/year
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class, by default it's "BW" Biweekly
     *
     * ***************************************
     *             r * ((1 + r) ^ n)
     *  A = P * --------------------------
     *             ((1 + r) ^ n) - 1
     * ***************************************
     *
     * A — Payment Amount per Period
     * P — Loan Amount
     * r — Interest Rate per Period
     * n — Total Number of Payments or Periods
     *
     * @return float|int
     * @throws AprException
     */

    public function calcFirstPayment($loanAmount=0, float $annualInterestRate=0, $paymentFrequency='BW')
    {
        /** Validations **/

        // Interest Rate, Principal amount and periods must be positive and > 0
        if ($annualInterestRate <= 0) {
            throw new AprException(AprException::ERR_Annual_Interest_Rate);
        }

        // Interest loan amount must be positive and > 0
        if ($loanAmount <= 0) {
            throw new AprException(AprException::ERR_Loan_Amount);
        }

        // Payment frequency must be a valid one
        if ($this->isValidFrequency($paymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        /** Procedure **/

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

    }




    /**
     * This function implements the "General Equation" used to calculate both the Initial Estimated APR
     * (Average Percentage Rate) and the refined APR during the iteration + interpolation procedure as
     * explained in the Appendix J to Part 1026 — Annual Percentage Rate Computations for Closed-End
     * Credit Transactions using the special form for the General Equation.
     *
     * @param $totalPeriods -> Total number of periods for the complete loan
     * @param $paymentAmount -> The refined payment, this is the most common recurrent payment in the loan
     * @param $initialPeriods -> The number of full unit-periods from the beginning of the term of the transaction to the jth payment
     * @param $fractions -> The fraction of a unit-period in the time interval from the beginning of the term of the transaction to the jth payment
     * @param $rate -> The percentage rate of finance charge per unit-period, expressed as a decimal equivalent
     * @return float|int
     *
     *                 Pj
     *  A = --------------------------
     *        ((1 + fi) * (1 + i)^tj
     *
     * Pj  = The amount of the jth payment
     * f  = The fraction of a unit-period in the time interval from the beginning of the term of the
     *      transaction to the jth payment
     * i  = The percentage rate of finance charge per unit-period, expressed as a decimal equivalent.
     * tj = The number of full unit-periods from the beginning of the term of the transaction to the jth payment
     *
     * @return float|int
     *
     * @throws AprException
     */

    public function generalEquation($totalPeriods, $paymentAmount, $initialPeriods, $fractions, $rate){

        /** Validations **/
        if ($totalPeriods <= 0)
        {
            throw new AprException(AprException::ERR_Period);
        }

        if ($paymentAmount <= 0)
        {
            throw new AprException(AprException::ERR_Estimated_First_Payment);
        }

        if ($initialPeriods <= 0)
        {
            throw new AprException(AprException::ERR_Initial_Period);
        }

        if ($fractions <0)
        {
            throw new AprException(AprException::ERR_Invalid_Fraction);
        }

        if ($rate <=0)
        {
            throw new AprException(AprException::ERR_Rate);
        }

        /** Procedure **/

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }




    /**
     * This function calculates the final APR value using the iteration + interpolation procedure explained
     * in the Appendix J to Part 1026 — Annual Percentage Rate Computations for Closed-End Credit Transactions
     * using the special form for the General Equation.
     *
     * @param $amount -> This is the disbursed amount, the actual amount paid to the person
     * @param $refinedPayment -> The periodic payment P, the refined payments calculated using the iterative function (refinePayments)
     * @param $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class
     * @param $APRGuess -> The first APR guess to start estimating from
     * @param $partial -> Odd days, as a fraction of a pay period.  10 days of a month is 0.33333... If no odd days
     *                  apply, then it must be zero (0)
     * @param $fullPeriods -> How many Full pay periods before the first payment.  Usually 1 if one complete period
     *                  of time passes from the date the loan was granted until the date of the first payment, If this is
     *                  irregular, then the fraction must me calculated
     *
     * @return float $result -> The calculated APR
     * @throws AprException
     */
    public function calculateFinalAPR($amount, $refinedPayment, $paymentFrequency, $APRGuess, $partial, $fullPeriods)
    {
        /** Validations **/

        if ($amount <= 0)
        {
            throw new AprException(AprException::ERR_APR_Amount);
        }

        if ($refinedPayment <= 0)
        {
            throw new AprException(AprException::ERR_APR_Periodic_Payment);
        }

        if ($APRGuess <=0)
        {
            throw new AprException(AprException::ERR_APR_Initial_Guess);
        }

        if ($partial <0)
        {
            throw new AprException(AprException::ERR_APR_Partial);
        }

        if ($fullPeriods <0)
        {
            throw new AprException(AprException::ERR_APR_Full_Periods);
        }

        $tempGuess = $APRGuess;

        $periods = $this->getPeriods($paymentFrequency,'T');
        $periodsPerYear = $this->getPeriods($paymentFrequency,'Y');

        /** Procedure **/

            //Calculate the first interpolation element
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

            //Calculate the second interpolation element
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

            // Interpolation
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

        return $result;
    }




    /**
     * This function creates the amortization table based on the data provided, this is the initial amoritzation table
     * this function doesn't include advanced payments or accumulation of interests.
     *
     * @param float $principalAmount -> Loan amount, this must include any fees that adds to the total amount of the loan
     * @param float $annualInterestRate -> Expressed as a decimal.  10% = 0.1   24.95% = 0.2495
     * @param float $refinedRepayment -> This is the recurring payment amount
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class, by default it's "BW" Biweekly
     * @param string $dateGranted -> The date the loan amount was deposited to the client
     * @param string $firstPaymentDate -> the date the first payment must be done
     * @return AmortizationScheduleDetails
     *              num -> This payment's number
     *              dueDate -> The date this payment was granted, then the date it's due
     *              days -> The number of days this payment is covering
     *              due_payment -> The amount us USD of this payment. It is a fixed amount for all periods, calculated using the formula provided in the methodology
     *              new_interest -> Interest generated during this period
     *              matured_interest -> Accumulated matured interest until and including this period
     *              fees -> this are the fees applied to this re-payment
     *              paid_interest -> This is the amount of interests paid during this period
     *              unpaid_interest -> Calculated interests from unpaid re-payments's interests
     *              paid_fees -> this are the fees applied to this re-payment
     *              unpaid_fees -> this are the fees applied to this re-payment
     *              principal_reduction -> The amount of USD that is being deducted from the principal in this period
     *              balance -> The amount of principal remaining
     *              paid_date -> The date the repayment was paid
     *              amount_paid -> The amount paid during this period
     *
     * @throws AprException
     */

    public function createInitialAmortizationTable(float $principalAmount=0, float $annualInterestRate=0, float $refinedRepayment = 0, $paymentFrequency='BW', $dateGranted = '', $firstPaymentDate = '')
    {
        /** Validations **/

        if ($annualInterestRate <= 0){
            throw new AprException(AprException::ERR_Annual_Interest_Rate);
        }

        if ($principalAmount <= 0)
        {
            throw new AprException(AprException::ERR_Principal);
        }

        if ($refinedRepayment <= 0)
        {
            throw new AprException(AprException::ERR_Re_payment);
        }

        // Payment frequency must be a valid one
        if ($this->isValidFrequency($paymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        // Check that the date is valid
        if (($this->isValidDate($firstPaymentDate) == false) || ( $this->isValidDate($dateGranted) == false))
        { throw new AprException(AprException::ERR_Invalid_Date);}

        /** Procedure **/

       // $table = new AmortizationScheduleDetails;

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
        return $table;
    }




    /**
     * This function refines the amount of the recurring re-payments based on the initial approximation and iterating with
     * the amortization table. The objective is to get re-payment amount as even as possible between all the periods
     * If there is a difference, it will be added to the last period.
     *
     * @param $principalAmount -> The amount of the loan
     * @param $annualInterestRate -> Expressed as a decimal.  10% = 0.1   24.95% = 0.2495
     * @param $estimatedFirstPayment -> The first estimated payment to be refined
     * @param string $paymentFrequency -> This is the frequency of the payment as expressed in the constant at the beginning of this class
     * @param $dateGranted -> The date the loan amount was deposited to the client
     * @param $firstPaymentDate -> the date the first payment must be done
     * @return float -> The refined re-payments
     * @throws AprException
     */


    public function refinePayments($principalAmount, $annualInterestRate, $estimatedFirstPayment, $paymentFrequency, $dateGranted, $firstPaymentDate){

        /** Validations **/

        if ($principalAmount <= 0){
            throw new AprException(AprException::ERR_Principal);
        }

        if ($annualInterestRate <= 0){
            throw new AprException(AprException::ERR_Annual_Interest_Rate);
        }

        if ($estimatedFirstPayment <= 0){
            throw new AprException(AprException::ERR_Estimated_First_Payment);
        }

        if ($this->isValidFrequency($paymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        if ($this->isValidDate($dateGranted) == false) {
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        if ($this->isValidDate($firstPaymentDate) == false) {
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        /** Procedure **/
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
    }




    /**
     * This function initializes the amortization table so it can be used to recalculate the interests and fees
     *
     * @param array $amortizationTable -> The amortization table to be initialized
     * @return array -> Returns the amortization table initialized ro be recalculated.
     * @throws AprException
     */
    public function initializeAmortizationTable($amortizationTable)
    {

        /** Validations **/

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
        return $changed->all();
    }




    /**
     * This function applies a payment to the amortization table. It returns an amortization table with
     * the payment column modified according to the date and the amount being paid.
     *
     * @param $date -> The date the payment occurs
     * @param float $paymentAmount -> The amount of the payment
     * @param $amortizationTable -> The amortization table to apply the payment
     * @return array -> the amortization table eith payment column update with the amount in place
     * @throws AprException
     */
    public function applyPayment ($date, float $paymentAmount, $amortizationTable){

        /** Validations */

        if( $this->isValidDate($date) == false)
        { throw new AprException(AprException::ERR_Invalid_Date);}

        if ($paymentAmount <= 0){
            throw new AprException(AprException::ERR_One_Time_Payment);
        }

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }

        /** Procedure */
// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION
        return $changed;
    }




    /**
     * @param $todayDate -> This is the date that will be used to check if the repayment is overdue
     *        usually, this is today's date.
     * @param $annualInterestRate -> Expressed as a decimal.  10% = 0.1   24.95% = 0.2495
     * @param $amortizationTable -> The amortization table to be modified
     * @return returns the amortization table in form of an array
     * @throws AprException
     */
    public function updateAmortizationTableInterests($todayDate, $annualInterestRate, $amortizationTable){

        /** Validations **/

        if( $this->isValidDate($todayDate) == false){
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        if ($annualInterestRate <= 0){
            throw new AprException(AprException::ERR_Annual_Interest_Rate);
        }

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }


        /** Procedure **/

// THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

        return collect($amortizationTable);
    }




    /**
     * This function returns the number of the repayment depending on the date provided.
     * If no repayment is found, then it returns null.
     *
     * @param $date -> The date to search
     * @param float $amortizationTable -> The amortization table to search the date from
     * @return int -> If found, returns the number of the repayment, if not, it returns null
     * @throws AprException
     */
    public function findRepayment ($date, $amortizationTable){

        /** Validations */

        if ($this->isValidDate($date) == false)
        { throw new AprException(AprException::ERR_Invalid_Date);}

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }

        /** Procedure */
        return $amortizationTable->firstWhere('dueDate', '>=', $date)['num'];
    }




    /**
     * This function changes the payment frequency
     *
     * @param $initialDate -> the date that will be used to look for the payment number to start the new
     *              payment frequency.
     * @param $annualInterestRate -> Expressed in decimal. 0.25 = 25%
     * @param $newPaymentFrequency -> The new payment frequency to be applied. As specified in the constant
     *          at the beginning of this class
     * @param $amortizationTable -> The amortization table to be changed.
     * @return
     * @throws AprException
     */

    public function changePaymentFrequency($initialDate, $annualInterestRate, $newPaymentFrequency, $amortizationTable){

        /** Validations **/

        if( $this->isValidDate($initialDate) == false){
            throw new AprException(AprException::ERR_Invalid_Date);
        }

        if ($this->isValidFrequency($newPaymentFrequency) == false){
            throw new AprException(AprException::ERR_Frequency);
        }

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }

        /** Procedure **/

        // THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

        return $amortizationTable;
    }




    /*********************************************************************************************
     * *******************************************************************************************
     * ********************************* CALC RELATED FUNCTIONS  *********************************
     * *******************************************************************************************
     *********************************************************************************************/

     /**
     * This function saves the data from the amortization table to the database
     *
     * @param $amortizationData -> The amortization table to be saved.
     * @param int $amortizationID -> If provided, this means that there is an existing amortization table in
     *        the data base and we want to overwrite it with this new one.  If not provided, it will create
     *        a save the amortization table in AmortizationScheduleDetails
     * @return bool -> True if saved, false if not.
     * @throws AprException
     */

    public function saveAmortization($amortizationData){
        /** Validations **/

        if (!$amortizationData){
            throw new AprException('ERR_Amortization_Data');
        }


        /** Procedure **/
        try {

            // THIS BLOCK OF CODE WAS REMOVED TO AVOID REVEALING PROTECTED INFORMATION

        }
        catch (\Exception $e) {
            /*log error*/
            $exception = 'Error Saving the amortization table -'.$e->getMessage().'--'.$e->getLine();
            Log::error($exception);
            return false;
        }
    }



    /**
     * This function saves the data from the amortization table to the database
     *
     * @param $amortizationTable -> The amortization table to be saved.
     * @param int $amortizationID -> If provided, this means that there is an existing amortization table in
     *        the data base and we want to overwrite it with this new one.  If not provided, it will create
     *        a save the amortization table in AmortizationScheduleDetails
     * @return bool -> True if saved, false if not.
     * @throws AprException
     */

    public function saveAmortizationTable($amortizationTable, $amortizationID = -1){
        /** Validations **/

        if ($amortizationTable->isEmpty()){
            throw new AprException(AprException::ERR_Amortization_Table);
        }

        if ($amortizationID < 0){
            throw new AprException(AprException::ERR_Missing_Amortization_Table_ID);
        }

        /** Procedure **/
        try {
            // We now build the rest of the amortization table rows
            $num = 0;
            while ($num <= count ($amortizationTable)-1) {
                $thisRecord = new AmortizationScheduleDetails;
                $thisRecord['amortization_schedule_id'] = $amortizationID;
                $thisRecord->fill($amortizationTable[$num]);
                $thisRecord->save();
                $num++;
            }
            return true;
        }
        catch (\Exception $e) {
            /*log error*/
            $exception = 'Error Saving the amortization table -'.$e->getMessage().'--'.$e->getLine();
            Log::error($exception);
            return false;
        }
    }
}
