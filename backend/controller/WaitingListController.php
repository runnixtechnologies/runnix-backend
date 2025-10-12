<?php
namespace Controller;

use Model\WaitingList;

class WaitingListController
{
    private $waitingList;

    public function __construct()
    {
        $this->waitingList = new WaitingList();
    }

    
public function handleFormSubmission($name, $email, $role, $status = 1)
{
    $this->waitingList->name = $name;
    $this->waitingList->email = $email;
    $this->waitingList->role = $role;
    $this->waitingList->status = (int) $status; // Ensure it's an integer

    if ($this->waitingList->insertWaitingList()) {
        return json_encode(["message" => "Successfully added to the waiting list."]);
    } else {
        return json_encode(["message" => "Email already exists in the database."]);
    }
}


}
?>