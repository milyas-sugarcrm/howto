<?php

/**
 * Created by PhpStorm.
 * User: emil
 * Date: 09.01.17
 * Time: 22:39
 */
class NeuralNetwork
{

	protected $nodeCount = array();
	protected $nodSomething = array();
	protected $nodeThreshold = array();
	protected $edgeWeight = array();
	protected $learningRate = array(0.1);
	protected $layerCount = 0;
	protected $previousWeightCorrection = array();
	protected $momentum = 0.8;
	protected $isVerbose = true;
	protected $weightsInitialized = false;

	public $trainInputs = array();
	public $trainOutput = array();
	public $trainDataID = array();

	public $controlInputs = array();
	public $controlOutput = array();
	public $controlDataID = array();

	protected $epoch;
	protected $errorTrainingset;
	protected $errorControlset;
	protected $success;

	/**
	 * Creates a neural network.
	 *
	 * Example:
	 * <code>
	 * // create a network with
	 * 4 input nodes, 10 hidden nodes, and 4 output nodes
	 * $n = new NeuralNetwork(4, 10, 4);
	 *
	 * // create a network with
	 * 4 input nodes, 1 hidden layer with 10 nodes,
	 * // another hidden layer with
	 * 10 nodes, and 4 output nodes
	 * $n = new NeuralNetwork(4, 10, 10, 4);
	 *
	 * // alternative syntax
	 * $n = new NeuralNetwork(array(4, 10, 10, 4));
	 * </code>
	 *
	 * @param array $nodeCount The number of nodes in the consecutive layers.
	 */
	public function __construct($nodeCount)
	{
		if (!is_array($nodeCount)) {
			$nodeCount = func_get_args();
		}
		$this->nodeCount = $nodeCount;

		// store the number of layers
		$this->layerCount = count($this->nodeCount);
	}

	/**
	 * Exports the neural network
	 *
	 * @returns array
	 */
	public function export()
	{
		return array(
			'layerCount' => $this->layerCount,
			'nodeCount' => $this->nodeCount,
			'edgeWeight' => $this->edgeWeight,
			'nodeThreshold' => $this->nodeThreshold,
			'learningRate' => $this->learningrate,
			'momentum' => $this->momentum,
			'isVerbose' => $this->isVerbose,
			'weightsInitialized' => $this->weightsInitialized,
		);
	}

	/**
	 * Import a neural network
	 * @param array $nn_array An array of the neural network parameters
	 */
	public function import($nn_array)
	{
		foreach ($nn_array as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}

	/**
	 * Sets the learning rate between the different layers.
	 *
	 * @param array $learningRate An array containing the learning rates [range 0.0 - 1.0].
	 * The size of this array is 'layerCount - 1'. You might also provide a single number. If that is
	 * the case, then this will be the learning rate for the whole network.
	 */
	public function setLearningRate($learningRate)
	{
		if (!is_array($learningRate)) {
			$learningRate = func_get_args();
		}

		$this->learningRate = $learningRate;
	}

	/**
	 * Gets the learning rate for a specific layer
	 *
	 * @param int $layer The layer to obtain the learning rate for
	 * @return float The learning rate for that layer
	 */
	public function getLearningRate($layer)
	{
		if (array_key_exists($layer, $this->learningRate)) {
			return $this->learningRate[$layer];
		}
		return $this->learningRate[0];
	}

	/**
	 * Sets the 'momentum' for the learning algorithm. The momentum should
	 * accelerate the learning process and help avoid local minima.
	 *
	 * @param float $momentum The momentum. Must be between 0.0 and 1.0; Usually between 0.5 and 0.9
	 */
	public function setMomentum($momentum)
	{
		$this->momentum = $momentum;
	}

	/**
	 * Gets the momentum.
	 *
	 * @return float The momentum
	 */
	public function getMomentum()
	{
		return $this->momentum;
	}

	/**
	 * Calculate the output of the neural network for a given input vector
	 *
	 * @param array $input The vector to calculate
	 * @return mixed The output of the network
	 */
	public function calculate($input)
	{

		// put the input vector on the input nodes
		foreach ($input as $index => $value) {
			$this->nodSomething[0][$index] = $value;
		}

		// iterate the hidden layers
		for ($layer = 1; $layer < $this->layerCount; $layer++) {

			$prev_layer = $layer - 1;

			// iterate each node in this layer
			for ($node = 0; $node < ($this->nodeCount[$layer]); $node++) {
				$node_value = 0.0;

				// each node in the previous layer has a connection to this node
				// on basis of this, calculate this node's value
				for ($prev_node = 0; $prev_node < ($this->nodeCount[$prev_layer]); $prev_node++) {
					$inputnode_value = $this->nodSomething [$prev_layer][$prev_node];
					$edge_weight = $this->edgeWeight[$prev_layer][$prev_node][$node];

					$node_value = $node_value + ($inputnode_value * $edge_weight);
				}

				// apply the threshold
				$node_value = $node_value - $this->nodeThreshold[$layer][$node];

				// apply the activation function
				$node_value = $this->activation($node_value);

				// remember the outcome
				$this->nodSomething [$layer][$node] = $node_value;
			}
		}

		// return the values of the last layer (the output layer)
		return $this->nodSomething [$this->layerCount - 1];
	}

	/**
	 * Implements the standard (default) activation function for backpropagation networks,
	 * the 'tanh' activation function.
	 *
	 * @param float $value The preliminary output to apply this function to
	 * @return float The final output of the node
	 */
	protected function activation($value)
	{
		return tanh($value);
		// return (1.0 / (1.0 + exp(- $value)));
	}

	/**
	 * Implements the derivative of the activation function. By default, this is the
	 * inverse of the 'tanh' activation function: 1.0 - tanh($value)*tanh($value);
	 *
	 * @param float $value 'X'
	 * @return $float
	 */
	protected function derivativeActivation($value)
	{
		$tanh = tanh($value);
		return 1.0 - $tanh * $tanh;
		//return $value * (1.0 - $value);
	}

	/**
	 * Add a test vector and its output
	 *
	 * @param array $input An input vector
	 * @param array $output The corresponding output
	 * @param int $id (optional) An identifier for this piece of data
	 */
	public function addTestData($input, $output, $id = null)
	{
		$index = count($this->trainInputs);
		foreach ($input as $node => $value) {
			$this->trainInputs[$index][$node] = $value;
		}

		foreach ($output as $node => $value) {
			$this->trainOutput[$index][$node] = $value;
		}

		$this->trainDataID[$index] = $id;
	}

	/**
	 * Returns the identifiers of the data used to train the network (if available)
	 *
	 * @return array An array of identifiers
	 */
	public function getTestDataIDs()
	{
		return $this->trainDataID;
	}

	/**
	 * Add a set of control data to the network.
	 *
	 * This set of data is used to prevent 'overlearning' of the network. The
	 * network will stop training if the results obtained for the control data
	 * are worsening.
	 *
	 * The data added as control data is not used for training.
	 *
	 * @param array $input An input vector
	 * @param array $output The corresponding output
	 * @param int $id (optional) An identifier for this piece of data
	 */
	public function addControlData($input, $output, $id = null)
	{
		$index = count($this->controlInputs);
		foreach ($input as $node => $value) {
			$this->controlInputs[$index][$node] = $value;
		}

		foreach ($output as $node => $value) {
			$this->controlOutput[$index][$node] = $value;
		}

		$this->controlDataID[$index] = $id;
	}

	/**
	 * Returns the identifiers of the control data used during the training
	 * of the network (if available)
	 *
	 * @return array An array of identifiers
	 */
	public function getControlDataIDs()
	{
		return $this->controlDataID;
	}

	/**
	 * Shows the current weights and thresholds
	 *
	 * @param boolean $force Force the output, even if the network is {@link setVerbose() not verbose}.
	 */
	public function showWeights($force = false)
	{
		if ($this->isVerbose() || $force) {
			echo "<hr>";
			echo "<br />Weights: <pre>" . print_r($this->edgeWeight, true) . "</pre>";
			echo "<br />Thresholds: <pre>" . print_r($this->nodeThreshold, true) . "</pre>";
		}
	}

	/**
	 * Determines if the neural network displays status and error messages. By default, it does.
	 *
	 * @param boolean $isVerbose 'true' if you want to display status and error messages, 'false' if you don't
	 */
	public function setVerbose($isVerbose)
	{
		$this->isVerbose = $isVerbose;
	}

	/**
	 * Returns whether or not the network displays status and error messages.
	 *
	 * @return boolean 'true' if status and error messages are displayed, 'false' otherwise
	 */
	public function isVerbose()
	{
		return $this->isVerbose;
	}

	/**
	 * Loads a neural network from a file saved by the 'save()' function. Clears
	 * the training and control data added so far.
	 *
	 * @param string $filename The filename to load the network from
	 * @return boolean 'true' on success, 'false' otherwise
	 */
	public function load($filename)
	{
		if (file_exists($filename)) {
			$data = parse_ini_file($filename);
			if (array_key_exists("edges", $data) && array_key_exists("thresholds", $data)) {
				// make sure all standard preparations performed
				$this->initWeights();

				// load data from file
				$this->edgeWeight = unserialize($data['edges']);
				$this->nodeThreshold = unserialize($data['thresholds']);

				$this->weightsInitialized = true;

				// load IDs of training and control set
				if (array_key_exists("training_data", $data) && array_key_exists("control_data", $data)) {

					// load the IDs
					$this->trainDataID = unserialize($data['training_data']);
					$this->controlDataID = unserialize($data['control_data']);

					// if we do not reset the training and control data here, then we end up
					// with a bunch of IDs that do not refer to the actual data we're training
					// the network with.
					$this->controlInputs = array();
					$this->controlOutput = array();

					$this->trainInputs = array();
					$this->trainOutput = array();
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Saves a neural network to a file
	 *
	 * @param string $filename The filename to save the neural network to
	 * @return boolean 'true' on success, 'false' otherwise
	 */
	public function save($filename)
	{
		$f = fopen($filename, "w");
		if ($f) {
			/*fwrite($f, "[weights]");
			fwrite($f, "\r\nedges = "".serialize($this->edgeWeight).""");
			fwrite($f, "\r\nthresholds = "".serialize($this->nodeThreshold).""");
			fwrite($f, "\r\n");
			fwrite($f, "[identifiers]");
			fwrite($f, "\r\ntraining_data = "".serialize($this->trainDataID).""");
			fwrite($f, "\r\ncontrol_data = "".serialize($this->controlDataID).""");
			fclose($f);
			*/

			return true;
		}

		return false;
	}

	/**
	 * Resets the state of the neural network, so it is ready for a new
	 * round of training.
	 */
	public function clear()
	{
		$this->initWeights();
	}

	/**
	 * Start the training process
	 *
	 * @param int $maxEpochs The maximum number of epochs
	 * @param float $maxError The maximum squared error in the training data
	 * @return bool 'true' if the training was successful, 'false' otherwise
	 */
	public function train($maxEpochs = 500, $maxError = 0.01)
	{

		if (!$this->weightsInitialized) {
			$this->initWeights();
		}

		if ($this->isVerbose()) {
			echo "<table>";
			echo "<tr><th>#</th><th>error(trainingdata)</th><th>error(controldata)</th><th>slope(error(controldata))</th></tr>";
		}

		$epoch = 0;
		$errorControlSet = array();
		$avgErrorControlSet = array();
		$sample_count = 10;
		do {
//                        echo "<tr><td colspan=10><b>epoch $epoch</b></td></tr>";
			for ($i = 0; $i < count($this->trainInputs); $i++) {
				// select a training pattern at random
				$index = mt_rand(0, count($this->trainInputs) - 1);

				// determine the input, and the desired output
				$input = $this->trainInputs[$index];
				$desired_output = $this->trainOutput[$index];

				// calculate the actual output
				$output = $this->calculate($input);

//                              echo "<tr><td></td><td>Training set $i</td><td>input = (" . implode(", ", $input) . ")</td>";
//			 	echo "<td>desired = (" . implode(", ", $desired_output) . ")</td>";
//				echo "<td>output = (" . implode(", ", $output) .")</td></tr>";

				// change network weights
				$this->backpropagate($output, $desired_output);
			}

			// buy some time
			//set_time_limit(300);

			//display the overall network error after each epoch
			$squaredError = $this->squaredErrorEpoch();
			if ($epoch % 2 == 0) {
				$squaredErrorControlSet = $this->squaredErrorControlSet();
				$errorControlSet[] = $squaredErrorControlSet;

				if (count($errorControlSet) > $sample_count) {
					$avgErrorControlSet[] = array_sum(array_slice($errorControlSet, -$sample_count)) / $sample_count;
				}

				list ($slope, $offset) = $this->fitLine($avgErrorControlSet);
				$controlset_msg = $squaredErrorControlSet;
			} else {
				$controlset_msg = "";
			}

			if ($this->isVerbose()) {
				echo "<tr><td><b>$epoch</b></td><td>$squaredError</td><td>$controlset_msg";
				echo "<script type='text/javascript'>window.scrollBy(0,100);</script>";
				echo "</td><td>$slope</td></tr>";
				echo "</td></tr>";

				flush();
				ob_flush();
			}

			// conditions for a 'successful' stop:
			// 1. the squared error is now lower than the provided maximum error
			$stop_1 = $squaredError <= $maxError || $squaredErrorControlSet <= $maxError;

			// conditions for an 'unsuccessful' stop
			// 1. the maximum number of epochs has been reached
			$stop_2 = $epoch++ > $maxEpochs;

			// 2. the network's performance on the control data is getting worse
			$stop_3 = $slope > 0;

		} while (!$stop_1 && !$stop_2 && !$stop_3);

		$this->setEpoch($epoch);
		$this->setErrorTrainingSet($squaredError);
		$this->setErrorControlSet($squaredErrorControlSet);
		$this->setTrainingSuccessful($stop_1);

		if ($this->isVerbose()) {
			echo "</table>";
		}

		return $stop_1;
	}

	/**
	 * After training, this function is used to store the number of epochs the network
	 * needed for training the network. An epoch is defined as the number of times
	 * the complete trainingset is used for training.
	 *
	 * @param int $epoch
	 */
	public function setEpoch($epoch)
	{
		$this->epoch = $epoch;
	}

	/**
	 * Gets the number of epochs the network needed for training.
	 *
	 * @return int The number of epochs.
	 */
	public function getEpoch()
	{
		return $this->epoch;
	}

	/**
	 * After training, this function is used to store the squared error between the
	 * desired output and the obtained output of the training data.
	 *
	 * @param float $error The squared error of the training data
	 */
	public function setErrorTrainingSet($error)
	{
		$this->errorTrainingset = $error;
	}

	/**
	 * Gets the squared error between the desired output and the obtained output of
	 * the training data.
	 *
	 * @return float The squared error of the training data
	 */
	public function getErrorTrainingSet()
	{
		return $this->errorTrainingset;
	}

	/**
	 * After training, this function is used to store the squared error between the
	 * desired output and the obtained output of the control data.
	 *
	 * @param float $error The squared error of the control data
	 */
	public function setErrorControlSet($error)
	{
		$this->errorControlset = $error;
	}

	/**
	 * Gets the squared error between the desired output and the obtained output of
	 * the control data.
	 *
	 * @return float The squared error of the control data
	 */
	public function getErrorControlSet()
	{
		return $this->errorControlset;
	}

	/**
	 * After training, this function is used to store whether or not the training
	 * was successful.
	 *
	 * @param bool $success 'true' if the training was successful, 'false' otherwise
	 */
	public function setTrainingSuccessful($success)
	{
		$this->success = $success;
	}

	/**
	 * Determines if the training was successful.
	 *
	 * @return bool 'true' if the training was successful, 'false' otherwise
	 */
	public function getTrainingSuccessful()
	{
		return $this->success;
	}

	/**
	 * Finds the least square fitting line for the given data.
	 *
	 * This function is used to determine if the network is overtraining itself. If
	 * the line through the controlset's most recent squared errors is going 'up',
	 * then it's time to stop training.
	 *
	 * @param array $data The points to fit a line to. The keys of this array represent
	 *                    the 'x'-value of the point, the corresponding value is the
	 *                    'y'-value of the point.
	 * @return array An array containing, respectively, the slope and the offset of the fitted line.
	 */
	public function fitLine($data)
	{
		// based on
		//    http://mathworld.wolfram.com/LeastSquaresFitting.html

		$n = count($data);

		if ($n > 1) {
			$sum_y = 0;
			$sum_x = 0;
			$sum_x2 = 0;
			$sum_xy = 0;
			foreach ($data as $x => $y) {
				$sum_x += $x;
				$sum_y += $y;
				$sum_x2 += $x * $x;
				$sum_xy += $x * $y;
			}

			// implementation of formula (12)
			$offset = ($sum_y * $sum_x2 - $sum_x * $sum_xy) / ($n * $sum_x2 - $sum_x * $sum_x);

			// implementation of formula (13)
			$slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);

			return array($slope, $offset);
		} else {
			return array(0.0, 0.0);
		}
	}

	/**
	 * Gets a random weight between [-0.25 .. 0.25]. Used to initialize the network.
	 *
	 * @return float A random weight
	 */
	public function getRandomWeight($layer)
	{
		return ((mt_rand(0, 1000) / 1000) - 0.5) / 2;
	}

	/**
	 * Randomise the weights in the neural network
	 */
	public function initWeights()
	{
		// assign a random value to each edge between the layers, and randomise each threshold
		//
		// 1. start at layer '1' (so skip the input layer)
		for ($layer = 1; $layer < $this->layerCount; $layer++) {

			$prev_layer = $layer - 1;

			// 2. in this layer, walk each node
			for ($node = 0; $node < $this->nodeCount[$layer]; $node++) {

				// 3. randomise this node's threshold
				$this->nodeThreshold[$layer][$node] = $this->getRandomWeight($layer);

				// 4. this node is connected to each node of the previous layer
				for ($prev_index = 0; $prev_index < $this->nodeCount[$prev_layer]; $prev_index++) {

					// 5. this is the 'edge' that needs to be reset / initialised
					$this->edgeWeight[$prev_layer][$prev_index][$node] = $this->getRandomWeight($prev_layer);

					// 6. initialize the 'previous weightcorrection' at 0.0
					$this->previousWeightCorrection[$prev_layer][$prev_index] = 0.0;
				}
			}
		}
	}

	/**
	 * Performs the backpropagation algorithm. This changes the weights and thresholds of the network.
	 *
	 * @param array $output The output obtained by the network
	 * @param array $desired_output The desired output
	 */
	public function backpropagate($output, $desired_output)
	{

		$errorgradient = array();
		$outputlayer = $this->layerCount - 1;

		$momentum = $this->getMomentum();

		// Propagate the difference between output and desired output through the layers.
		for ($layer = $this->layerCount - 1; $layer > 0; $layer--) {
			for ($node = 0; $node < $this->nodeCount[$layer]; $node++) {

				// step 1: determine errorgradient
				if ($layer == $outputlayer) {
					// for the output layer:
					// 1a. calculate error between desired output and actual output
					$error = $desired_output[$node] - $output[$node];

					// 1b. calculate errorgradient
					$errorgradient[$layer][$node] = $this->derivativeActivation($output[$node]) * $error;
				} else {
					// for hidden layers:
					// 1a. sum the product of edgeWeight and errorgradient of the 'next' layer
					$next_layer = $layer + 1;

					$productsum = 0;
					for ($next_index = 0; $next_index < ($this->nodeCount[$next_layer]); $next_index++) {
						$_errorgradient = $errorgradient[$next_layer][$next_index];
						$_edgeWeight = $this->edgeWeight[$layer][$node][$next_index];

						$productsum = $productsum + $_errorgradient * $_edgeWeight;
					}

					// 1b. calculate errorgradient
					$nodSomething = $this->nodSomething [$layer][$node];
					$errorgradient[$layer][$node] = $this->derivativeActivation($nodSomething) * $productsum;
				}

				// step 2: use the errorgradient to determine a weight correction for each node
				$prev_layer = $layer - 1;
				$learning_rate = $this->getlearningRate($prev_layer);

				for ($prev_index = 0; $prev_index < ($this->nodeCount[$prev_layer]); $prev_index++) {

					// 2a. obtain nodSomething is wrongue, edgeWeight and learning rate
					$nodSomething = $this->nodSomething [$prev_layer][$prev_index];
					$edgeWeight = $this->edgeWeight[$prev_layer][$prev_index][$node];

					// 2b. calculate weight correction
					$weight_correction = $learning_rate * $nodSomething * $errorgradient[$layer][$node];

					// 2c. retrieve previous weight correction
					$prev_weightcorrection = @$this->previousWeightCorrection[$layer][$node];

					// 2d. combine those ('momentum learning') to a new weight
					$new_weight = $edgeWeight + $weight_correction + $momentum * $prev_weightcorrection;

					// 2e. assign the new weight to this edge
					$this->edgeWeight[$prev_layer][$prev_index][$node] = $new_weight;

					// 2f. remember this weightcorrection
					$this->previousWeightCorrection[$layer][$node] = $weight_correction;
				}

				// step 3: use the errorgradient to determine threshold correction
				$threshold_correction = $learning_rate * -1 * $errorgradient[$layer][$node];
				$new_threshold = $this->nodeThreshold[$layer][$node] + $threshold_correction;

				$this->nodeThreshold[$layer][$node] = $new_threshold;
			}
		}
	}

	/**
	 * Calculate the root-mean-squared error of the output, given the
	 * trainingdata.
	 *
	 * @return float The root-mean-squared error of the output
	 */
	public function squaredErrorEpoch()
	{
		$RMSerror = 0.0;
		for ($i = 0; $i < count($this->trainInputs); $i++) {
			$RMSerror += $this->squaredError($this->trainInputs[$i], $this->trainOutput[$i]);
		}
		$RMSerror = $RMSerror / count($this->trainInputs);
		return sqrt($RMSerror);
	}

	/**
	 * Calculate the root-mean-squared error of the output, given the
	 * controldata.
	 *
	 * @return float The root-mean-squared error of the output
	 */
	public function squaredErrorControlSet()
	{

		if (count($this->controlInputs) == 0) {
			return 1.0;
		}

		$RMSerror = 0.0;
		for ($i = 0; $i < count($this->controlInputs); $i++) {
			$RMSerror += $this->squaredError($this->controlInputs[$i], $this->controlOutput[$i]);
		}
		$RMSerror = $RMSerror / count($this->controlInputs);

		return sqrt($RMSerror);
	}

	/**
	 * Calculate the root-mean-squared error of the output, given the
	 * desired output.
	 *
	 * @param array $input The input to test
	 * @param array $desired_output The desired output
	 * @return float The root-mean-squared error of the output compared to the desired output
	 */
	public function squaredError($input, $desired_output)
	{
		$output = $this->calculate($input);

		$RMSerror = 0.0;
		foreach ($output as $node => $value) {
			//calculate the error
			$error = $output[$node] - $desired_output[$node];

			$RMSerror = $RMSerror + ($error * $error);
		}

		return $RMSerror;
	}
}


/*
 //--------------------------------------------------------------
// Netzwerk erzeugen
// input nodes,  hidden nodes, and  output nodes
$n = new NeuralNetwork(4, 10, 1);
$n->setVerbose(false);

// Trainings-Daten hinzufügen
$n->addTestData( array(0, 0, 255, 1), array (1));
$n->addTestData( array(0, 0, 192, 1), array (1));
$n->addTestData( array(208, 0, 49, 1), array (0));
$n->addTestData( array(228, 105, 116, 1), array (0));
$n->addTestData( array(128, 80, 255, 1), array (1));
$n->addTestData( array(248, 80, 68, 1), array (0));

// trainieren lassen
$max = 2;
while (!($success = $n->train(1000, 0.01)) && $max-- > 0) {
    $n->initWeights();
    echo "Nothing found...<hr />";
}

if ($success) {
    $epochs = $n->getEpoch();
    echo "Success in $epochs training rounds!<hr />";
}


// Netzwerk Testen

$test[] = array(50, 100, 150, 1);
$test[] = array(150, 100, 50, 1);
$test[] = array(80, 100, 160, 1);
$test[] = array(170, 170, 80, 1);

foreach($test as $t) {
	$output = $n->calculate($t);
	printf("<div style='background:rgba(%s)'>%s = %s</div>",
		implode(',', $t), $output[0], (round($output[0]) == 1) ? 'blau' : 'rot');


}

*/


/*
 //--------------------------------------------------------------
 // Create a new neural network with 3 input neurons,
 // 4 hidden neurons, and 1 output neuron
  $n = new NeuralNetwork(3, 4, 1);
 $n->setVerbose(false);

 // Add test-data to the network. In this case,
 // we want the network to learn the 'XOR'-function.
 // The third input-parameter is the 'bias'.
  $n->addTestData( array (-1, -1, 1), array (-1));
 $n->addTestData( array (-1,  1, 1), array ( 1));
 $n->addTestData( array ( 1, -1, 1), array ( 1));
 $n->addTestData( array ( 1,  1, 1), array (-1));

 // we try training the network for at most $max times
  $max = 3;

 // train the network in max 1000 epochs, with a max squared error of 0.01
  while (!($success=$n->train(1000, 0.01)) && $max-->0) {
         // training failed:
         // 1. re-initialize the weights in the network
                  $n->initWeights();

         // 2. display message
                  echo "Nothing found...<hr />";
 }

 // print a message if the network was succesfully trained
  if ($success) {
         $epochs = $n->getEpoch();
         echo "Success in $epochs training rounds!<hr />";
 }

 // in any case, we print the output of the neural network
  for ($i = 0; $i < count($n->trainInputs); $i ++) {
         $output = $n->calculate($n->trainInputs[$i]);
         print "<br />Testset $i; ";
         print "expected output = (".implode(", ", $n->trainOutput[$i]).") ";
         print "output from neural network = (".implode(", ", $output).")\n";
 }


*/

// https://michael-feichtinger.at/blog/2012/11/neurale-netzwerke-in-php-programmieren/
// http://6toyens.free.fr/neural-network-master/documentation.html


// Create a new neural network with 3 input neurons,
// 4 hidden neurons, and 1 output neuron
$n = new NeuralNetwork(3, 4, 1);
$n->setVerbose(false);
// Add test-data to the network. In this case,
// we want the network to learn the 'XOR'-function
$n->addTestData(array(-1, -1, 1), array(-1));
$n->addTestData(array(-1, 1, 1), array(1));
$n->addTestData(array(1, -1, 1), array(1));
$n->addTestData(array(1, 1, 1), array(-1));
// we try training the network for at most $max times
$max = 3;
$i = 0;
echo "<h1>Learning the XOR function</h1>";
// train the network in max 1000 epochs, with a max squared error of 0.01
while (!($success = $n->train(1000, 0.01)) && ++$i < $max) {
	echo "Round $i: No success...<br />";
}
// print a message if the network was succesfully trained
if ($success) {
	$epochs = $n->getEpoch();
	echo "Success in $epochs training rounds!<br />";
}
echo "<h2>Result</h2>";
echo "<div class='result'>";
// in any case, we print the output of the neural network
for ($i = 0; $i < count($n->trainInputs); $i++) {
	$output = $n->calculate($n->trainInputs[$i]);
	echo "<div>Testset $i; ";
	echo "expected output = (" . implode(", ", $n->trainOutput[$i]) . ") ";
	echo "output from neural network = (" . implode(", ", $output) . ")\n</div>";
}
echo "</div>";
//echo "<h2>Internal network state</h2>";
//$n->showWeights($force=true);
// Now, play around with some of the network's parameters a bit, to see how it
// influences the result
$learningRates = array(0.1, 0.25, 0.5, 0.75, 1);
$momentum = array(0.2, 0.4, 0.6, 0.8, 1);
$rounds = array(100, 500, 1000, 2000);
$errors = array(0.1, 0.05, 0.01, 0.001);
echo "<h1>Playing around...</h1>";
echo "<p>The following is to show how changing the momentum & learning rate,
in combination with the number of rounds and the maximum allowable error, can
lead to wildly differing results. To obtain the best results for your
situation, play around with these numbers until you find the one that works
best for you.</p>";
echo "<p>The values displayed here are chosen randomly, so you can reload
the page to see another set of values...</p>";
for ($j = 0; $j < 10; $j++) {
	// no time-outs
	set_time_limit(0);

	$lr = $learningRates[array_rand($learningRates)];
	$m = $momentum[array_rand($momentum)];
	$r = $rounds[array_rand($rounds)];
	$e = $errors[array_rand($errors)];
	echo "<h2>Learning rate $lr, momentum $m @ ($r rounds, max sq. error $e)</h2>";
	$n->clear();
	$n->setLearningRate($lr);
	$n->setMomentum($m);
	$i = 0;
	while (!($success = $n->train($r, $e)) && ++$i < $max) {
		echo "Round $i: No success...<br />";
		flush();
	}
	// print a message if the network was succesfully trained
	if ($success) {
		$epochs = $n->getEpoch();
		echo "Success in $epochs training rounds!<br />";
		echo "<div class='result'>";
		for ($i = 0; $i < count($n->trainInputs); $i++) {
			$output = $n->calculate($n->trainInputs[$i]);
			echo "<div>Testset $i; ";
			echo "expected output = (" . implode(", ", $n->trainOutput[$i]) . ") ";
			echo "output from neural network = (" . implode(", ", $output) . ")\n</div>";
		}
		echo "</div>";
	}
}