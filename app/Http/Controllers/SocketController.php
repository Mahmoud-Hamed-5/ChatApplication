<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Chat_request;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Psy\Readline\Hoa\Console;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


class SocketController extends Controller implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients->attach($connection);

        $querystring = $connection->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            User::where('token', $queryarray['token'])->update(['connection_id' => $connection->resourceId, 'user_status' => 'Online']);

            $user_id = User::select('id')->where('token', $queryarray['token'])->get();

            $data['id'] = $user_id[0]->id;

            $data['status'] = 'Online';

            foreach ($this->clients as $client) {
                if ($client->resourceId != $connection->resourceId) {
                    $client->send(json_encode($data));
                }
            }
        }
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {
        $data = json_decode($message);

        if (isset($data->type)) {
            if ($data->type == 'request_load_unconnected_user') {
                $user_data = User::select('id', 'name', 'gender', 'user_status', 'user_image')
                    ->where('id', '!=', $data->from_user_id)
                    ->orderBy('name', 'ASC')
                    ->get();

                $sub_data = array();
                foreach ($user_data as $row) {

                    $chat_request = Chat_request::select('id')
                        ->where(function ($query) use ($data, $row) {
                            $query->where('from_user_id', $data->from_user_id)
                                ->where('to_user_id', $row->id);
                        })
                        ->orWhere(function ($query) use ($data, $row) {
                            $query->where('from_user_id', $row->id)
                                ->where('to_user_id', $data->from_user_id);
                        })->get();

                    if ($chat_request->count() == 0) {
                        $sub_data[] = array(
                            'id'          =>   $row['id'],
                            'name'        =>   $row['name'],
                            'gender'      =>   $row['gender'],
                            'status'      =>   $row['status'],
                            'user_image'  =>   $row['user_image']
                        );
                    }
                }

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $send_data['data'] = $sub_data;

                $send_data['response_load_unconnected_user'] = true;

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'request_search_user') {
                $user_data = User::select('id', 'name', 'gender', 'user_status', 'user_image')
                    ->where('id', '!=', $data->from_user_id)
                    ->where('name', 'like', '%' . $data->search_query . '%')
                    ->orderBy('name', 'ASC')
                    ->get();

                $sub_data = array();
                foreach ($user_data as $row) {

                    $chat_request = Chat_request::select('id')
                        ->where(function ($query) use ($data, $row) {
                            $query->where('from_user_id', $data->from_user_id)
                                ->where('to_user_id', $row->id);
                        })
                        ->orWhere(function ($query) use ($data, $row) {
                            $query->where('from_user_id', $row->id)
                                ->where('to_user_id', $data->from_user_id);
                        })->get();

                    if ($chat_request->count() == 0) {
                        $sub_data[] = array(
                            'id'          =>   $row['id'],
                            'name'        =>   $row['name'],
                            'gender'      =>   $row['gender'],
                            'status'      =>   $row['status'],
                            'user_image'  =>   $row['user_image']
                        );
                    }
                }

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $send_data['data'] = $sub_data;

                $send_data['response_search_user'] = true;

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'request_chat_user') {

                $chat_request = new Chat_request();

                $chat_request->from_user_id = $data->from_user_id;
                $chat_request->to_user_id = $data->to_user_id;
                $chat_request->status = 'Pending';

                $chat_request->save();

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $receiver_connection_id = User::select('connection_id')
                    ->where('id', $data->to_user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $send_data['response_from_user_chat_request'] = true;
                        $client->send(json_encode($send_data));
                    }

                    if ($client->resourceId == $receiver_connection_id[0]->connection_id) {
                        $send_data['user_id'] = $data->to_user_id;
                        $send_data['response_to_user_chat_request'] = true;
                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'request_load_unread_notification') {
                $notification_data = Chat_request::select('id', 'from_user_id', 'to_user_id', 'status')
                    ->where('status', '!=', 'Approved')
                    ->where(function ($query) use ($data) {
                        $query->where('from_user_id', $data->user_id)
                            ->orWhere('to_user_id', $data->user_id);
                    })
                    ->orderBy('id', 'ASC')->get();


                $sub_data = array();
                foreach ($notification_data as $row) {
                    $user_id = '';
                    $notification_type = '';

                    if ($row->from_user_id == $data->user_id) {
                        $user_id = $row->to_user_id;
                        $notification_type = 'Sent Request';
                    } else {
                        $user_id = $row->from_user_id;
                        $notification_type = 'Received Request';
                    }

                    $user_data = User::select('name', 'gender', 'user_image')
                        ->where('id', $user_id)
                        ->first();

                    $sub_data[] = array(
                        'id'                =>  $row->id,
                        'from_user_id'      =>  $row->from_user_id,
                        'to_user_id'        =>  $row->to_user_id,
                        'name'              =>  $user_data->name,
                        'gender'            =>  $user_data->gender,
                        'notification_type' =>  $notification_type,
                        'status'            =>  $row->status,
                        'user_image'        =>  $user_data->user_image
                    );
                }

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $send_data['response_load_notification'] = true;
                        $send_data['data'] = $sub_data;
                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'request_process_chat_request') {
                Chat_request::where('id', $data->chat_request_id)
                    ->update(['status' => $data->action]);

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $receiver_connection_id = User::select('connection_id')
                    ->where('id', $data->to_user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    $send_data['response_process_chat_request'] = true;

                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $send_data['user_id'] = $data->from_user_id;
                    }

                    if ($client->resourceId == $receiver_connection_id[0]->connection_id) {
                        $send_data['user_id'] = $data->to_user_id;
                    }

                    $client->send(json_encode($send_data));
                }
            }

            if ($data->type == 'request_connected_chat_user') {
                $condition_1 = [
                    'from_user_id' => $data->from_user_id,
                    'to_user_id'   => $data->from_user_id
                ];

                $user_id_data = Chat_request::select('from_user_id', 'to_user_id')
                    ->orWhere($condition_1)
                    ->where('status', 'Approved')
                    ->get();



                $sub_data = array();

                foreach ($user_id_data as $user_id_row) {
                    $user_id = '';
                    if ($user_id_row->from_user_id != $data->from_user_id) {
                        $user_id = $user_id_row->from_user_id;
                    } else {
                        $user_id = $user_id_row->to_user_id;
                    }

                    $user_data = User::select('id', 'name', 'gender', 'user_image')
                        ->where('id', $user_id)
                        ->first();

                    $sub_data[] = array(
                        'id'          =>   $user_data->id,
                        'name'        =>   $user_data->name,
                        'gender'      =>   $user_data->gender,
                        'user_image'  =>   $user_data->user_image
                    );
                }

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $send_data['response_connected_chat_user'] = true;
                        $send_data['data'] = $sub_data;

                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'request_send_message') {
                //save chat message in database
                $chat = new Chat();

                $chat->from_user_id = $data->from_user_id;
                $chat->to_user_id = $data->to_user_id;
                $chat->chat_message = $data->message;
                $chat->message_status = 'Sent';

                $chat->save();

                $chat_message_id = $chat->id;

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $receiver_connection_id = User::select('connection_id')
                    ->where('id', $data->to_user_id)
                    ->get();


                //set the message status as sent
                $send_data['message_status'] = 'Sent';

                // check if the receiver is online
                foreach ($this->clients as $client) {
                    // if the reciever is online, update the message status to delivered
                    if ($client->resourceId == $receiver_connection_id[0]->connection_id) {
                        Chat::where('id', $chat_message_id)->update(['message_status' => 'Delivered']);

                        $send_data['message_status'] = 'Delivered';
                    }
                }

                // search all clients for sender and receiver, and send them the data
                foreach ($this->clients as $client) {
                    if (
                        $client->resourceId == $receiver_connection_id[0]->connection_id
                        || $client->resourceId == $sender_connection_id[0]->connection_id
                    ) {
                        $send_data['chat_message_id'] = $chat_message_id;
                        $send_data['message'] = $data->message;
                        $send_data['from_user_id'] = $data->from_user_id;
                        $send_data['to_user_id'] = $data->to_user_id;

                        $client->send(json_encode($send_data));
                    }
                }
            }


            if ($data->type == 'request_chat_history') {
                //
                $chat_data = Chat::select('id', 'from_user_id', 'to_user_id', 'chat_message', 'message_status')
                    ->where(function ($query) use ($data) {
                        $query->where('from_user_id', $data->from_user_id)
                            ->where('to_user_id', $data->to_user_id);
                    })
                    ->orWhere(function ($query) use ($data) {
                        $query->where('from_user_id', $data->to_user_id)
                            ->where('to_user_id', $data->from_user_id);
                    })
                    ->orderBy('id', 'ASC')
                    ->get();

                $send_data['chat_history'] = $chat_data;

                $receiver_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $receiver_connection_id[0]->connection_id) {
                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'update_chat_status') {
                //update chat status
                Chat::where('id', $data->chat_message_id)
                    ->update(['message_status' => $data->chat_message_status]);

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                        $send_data['update_message_status'] = $data->chat_message_status;

                        $send_data['chat_message_id'] = $data->chat_message_id;

                        $client->send(json_encode($send_data));
                    }
                }
            }

            if ($data->type == 'check_unread_message') {
                $chat_data = Chat::select('id', 'from_user_id', 'to_user_id')
                    ->where('from_user_id', $data->to_user_id)
                    ->where('to_user_id', $data->from_user_id)
                    ->where('message_status', '!=', 'Seen')
                    ->get();

                $sender_connection_id = User::select('connection_id')
                    ->where('id', $data->from_user_id)
                    ->get();

                $receiver_connection_id = User::select('connection_id')
                    ->where('id', $data->to_user_id)
                    ->get();

                   // error_log("count". $chat_data->count());

                // get count of unread messages
                $unread_messages_count = $chat_data->count();
                foreach ($chat_data as $row) {
                    Chat::where('id', $row->id)
                        ->update(['message_status' => 'Delivered']); // Seen

                    foreach ($this->clients as $client) {
                        if ($client->resourceId == $sender_connection_id[0]->connection_id) {
                            $send_data['count_unread_message'] = 1;
                            $send_data['unread_messages_count'] = $unread_messages_count;
                            $send_data['chat_message_id'] = $row->id;
                            $send_data['from_user_id'] = $row->from_user_id;
                            $client->send(json_encode($send_data));
                        }

                        if ($client->resourceId == $receiver_connection_id[0]->connection_id) {
                            $send_data['update_message_status'] = 'Delivered';
                            $send_data['chat_message_id'] = $row->id;
                            $send_data['unread_messages_count'] = $unread_messages_count;
                            $send_data['unread_msg'] = 1;
                            $send_data['from_user_id'] = $row->from_user_id;
                            $client->send(json_encode($send_data));
                        }

                        //$client->send(json_encode($send_data));
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->clients->detach($connection);

        $querystring = $connection->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token'])) {
            User::where('token', $queryarray['token'])
                ->update(['connection_id' => 0]);
        }
    }

    public function onError(ConnectionInterface $connection, \Exception $exception)
    {
        echo "An error has occurred: {$exception->getMessage()} \n";
        echo "An error has occurred: {$exception->getTraceAsString()} \n";
        $connection->close();
    }
}
