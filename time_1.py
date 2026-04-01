import time

# Set the duration of the quiz in seconds
quiz_duration = 300 # 5 minutes

# Get the start time of the quiz
start_time = time.time()

# Loop until the quiz duration is over
while True:
    # Get the current time
    current_time = time.time()

    elapsed_time = current_time - start_time
    # Calculate the elapsed time

    # Calculate the remaining time
    remaining_time = quiz_duration - elapsed_time

    # Check if the remaining time is less than or equal to zero
    if remaining_time <= 0:
        print("Time's up!")
        break

    # Display the remaining time in seconds
    print("Time remaining: ", int(remaining_time), "seconds")

    # Wait for one second before checking the time again
    time.sleep(1)