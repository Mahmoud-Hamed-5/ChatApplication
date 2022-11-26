@extends('layout')

@section('content')
    {{-- Users Lists Area --}}
    <div class="row">
        <div class="col-sm-4 col-lg-3">
            <div class="card">
                <div class="card-header"><b>Connected User</b></div>
                <div class="card-body" id="user_list">

                </div>
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="col-sm-4 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col col-md-6" id="chat_header"><b>Chat Box</b></div>
                        <div class="col col-md-6" id="close_chat_area"></div>
                    </div>
                </div>
                <div class="card-body" id="chat_area">

                </div>
            </div>
        </div>

        {{-- Search Area --}}
        <div class="col-sm-4 col-lg-3">
            <div class="card" style="height:255px; overflow-y: scroll;">
                <div class="card-header">
                    <div class="row">
                        <div class="col col-md-6" id="chat_header"><b>Search Users</b></div>
                    </div>
                    <div class="row">
                        <input type="text" id="search_people" class="form-control" placeholder="User Name ..."
                            autocomplete="off" onkeyup="search_user('{{ Auth::id() }}', this.value);" />
                    </div>
                </div>
                <div class="card-body">
                    <div id="search_people_area" class="mt-3"></div>
                </div>
            </div>
            <br />

            {{-- Notification Area --}}
            <div class="card" style="height:255px; overflow-y: scroll;">
                <div class="card-header"><b>Notification</b></div>
                <div class="card-body">
                    <ul class="list-group" id="notification_area">

                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        #chat_area {
            min-height: 500px;
            /*overflow-y: scroll*/
            ;
        }

        #chat_history {
            min-height: 500px;
            max-height: 500px;
            overflow-y: scroll;
            margin-bottom: 16px;
            background-color: #ece5dd;
            padding: 16px;
        }

        #user_list {
            min-height: 500px;
            max-height: 500px;
            overflow-y: scroll;
        }
    </style>
@endsection('content')


<script>
    var connection = new WebSocket('ws://127.0.0.1:8090/?token={{ auth()->user()->token }}');

    var from_user_id = "{{ Auth::user()->id }}";
    var to_user_id = "";

    connection.onopen = function(e) {
        console.log("Connection established");

        load_unconnected_user(from_user_id);

        load_unread_notification(from_user_id);

        load_connected_chat_user(from_user_id);
    };

    connection.onmessage = function(e) {
        var data = JSON.parse(e.data);
        if (data.response_load_unconnected_user || data.response_search_user) {
            var html = '';
            if (data.data.length > 0) {
                html += '<ul class="list-group">';
                for (var count = 0; count < data.data.length; count++) {
                    var user_image = '';
                    if (data.data[count].user_image != '') {
                        user_image = `<img src="{{ asset('images/profile-images/') }}/` + data.data[count]
                            .user_image + `" width="40" class="rounded-circle" />`;
                    } else {
                        if (data.data[count].gender == 'Male') {
                            user_image =
                                `<img src="{{ asset('images/profile-images/blank-profile-male.jpg') }}" width="40" class="rounded-circle" />`
                        } else {
                            user_image =
                                `<img src="{{ asset('images/profile-images/blank-profile-female.jpg') }}" width="40" class="rounded-circle" />`
                        }
                    }

                    html += `
				    <li class="list-group-item">
					<div class="row">
						<div class="col col-9">` + user_image + `&nbsp;` + data.data[count].name + `</div>
						<div class="col col-3">
							<button type="button"  title="Send chat request" name="send_request" class="btn btn-primary btn-sm float-end"
                            onclick="send_request(this, ` + from_user_id + `, ` + data.data[count].id + `)"><i class="fas fa-paper-plane"></i></button>
						</div>
					</div>
				    </li>
				    `;

                }
                html += '</ul>';
            } else {
                html = 'No User Found!';
            }

            document.getElementById('search_people_area').innerHTML = html;
        }

        if (data.response_from_user_chat_request) {
            search_user(from_user_id, document.getElementById('search_people').value);
            load_unread_notification(from_user_id);
        }

        if (data.response_to_user_chat_request) {
            load_unread_notification(data.user_id);
        }

        if (data.response_load_notification) {
            var html = '';
            for (var count = 0; count < data.data.length; count++) {
                var user_image = '';
                if (data.data[count].user_image != '') {
                    user_image = `<img src="{{ asset('images/profile-images/') }}/` +
                        data.data[count].user_image + `" width="40" class="rounded-circle" />`;
                } else {
                    if (data.data[count].gender == 'Male') {
                        user_image =
                            `<img src="{{ asset('images/profile-images/blank-profile-male.jpg') }}" width="40" class="rounded-circle" />`
                    } else {
                        user_image =
                            `<img src="{{ asset('images/profile-images/blank-profile-female.jpg') }}" width="40" class="rounded-circle" />`
                    }
                }

                html += `
				    <li class="list-group-item">
					<div class="row">
						<div class="col col-8">` + user_image + `&nbsp;` + data.data[count].name + `</div>
						<div class="col col-4">
				`;

                if (data.data[count].notification_type == 'Sent Request') {
                    if (data.data[count].status == 'Pending') {
                        html +=
                            '<button type="button" name="send_request" class="btn btn-warning btn-sm float-end">Request Sent</button>';
                    } else {
                        html +=
                            '<button type="button" name="send_request" class="btn btn-danger btn-sm float-end">Request Rejected</button>';
                    }
                } else {
                    if (data.data[count].status == 'Pending') {
                        html +=
                            '<button type="button" title="Reject" class="btn btn-danger btn-sm float-end" onclick="process_chat_request(' +
                            data.data[count].id + ', ' + data.data[count].from_user_id + ', ' + data.data[count]
                            .to_user_id + ', `Rejected`)"><i class="fas fa-times"></i></button>&nbsp;';
                        html +=
                            '<button type="button" title="Approve" class="btn btn-success btn-sm float-end" onclick="process_chat_request(' +
                            data.data[count].id + ', ' + data.data[count].from_user_id + ', ' + data.data[count]
                            .to_user_id + ', `Approved`)"><i class="fas fa-check"></i></button>';
                    } else {
                        html +=
                            '<button type="button" name="send_request" class="btn btn-danger btn-sm float-end">Request Rejected</button>';
                    }
                }

                html += `
					</div>
				</div>
			</li>
			`;
            }

            document.getElementById('notification_area').innerHTML = html;

        }

        if (data.response_process_chat_request) {
            load_unread_notification(data.user_id);

            load_connected_chat_user(data.user_id);
        }

        if (data.response_connected_chat_user) {
            var html = '<div class="list-group">';

            if (data.data.length > 0)
            {
                for (var count = 0; count < data.data.length; count++)
                {
                    html +=`
                            <a href="#" class="list-group-item d-flex justify-content-between align-items-start">

                            <div class="ms-2 me-auto">
                    `;

                    //var last_seen = '';

                    // if (data.data[count].user_status == 'Online') {
                    //     html += '<span class="text-success online_status_icon" id="status_' + data.data[count].id +
                    //         '"><i class="fas fa-circle"></i></span>';

                    //     last_seen = 'Online';
                    // } else {
                    //     html += '<span class="text-danger online_status_icon" id="status_' + data.data[count].id +
                    //         '"><i class="fas fa-circle"></i></span>';

                    //     last_seen = data.data[count].last_seen;
                    // }

                    var user_image = '';

                    if (data.data[count].user_image != '') {
                        user_image = `<img src="{{ asset('images/profile-images/') }}/` + data.data[count]
                            .user_image + `" width="35" class="rounded-circle" />`;
                    } else {
                        if (data.data[count].gender == 'Male') {
                            user_image =
                                `<img src="{{ asset('images/profile-images/blank-profile-male.jpg') }}" width="35" class="rounded-circle" />`
                        } else {
                            user_image =
                                `<img src="{{ asset('images/profile-images/blank-profile-female.jpg') }}" width="35" class="rounded-circle" />`
                        }
                    }



                    html += `
                            &nbsp; ` + user_image + `&nbsp;<b>` + data.data[count].name + `</b>
                            </div>
                        </a>
                    `;
                }
            } else {
                html += 'No User Found!';
            }

            html += '</div>';

            document.getElementById('user_list').innerHTML = html;

            //check_unread_message();
        }

    };

    function load_unconnected_user(from_user_id) {
        var data = {
            from_user_id: from_user_id,
            type: 'request_load_unconnected_user',
        };

        connection.send(JSON.stringify(data));
    }


    function search_user(from_user_id, search_query) {
        if (search_query.length > 0) {
            var data = {
                from_user_id: from_user_id,
                search_query: search_query,
                type: 'request_search_user'
            };
            connection.send(JSON.stringify(data));
        } else {
            load_unconnected_user(from_user_id);
        }
    }

    function send_request(element, from_user_id, to_user_id) {
        var data = {
            from_user_id: from_user_id,
            to_user_id: to_user_id,
            type: 'request_chat_user'
        };
        element.disabled = true;
        connection.send(JSON.stringify(data));
    }

    function load_unread_notification(user_id) {
        var data = {
            user_id: user_id,
            type: 'request_load_unread_notification'
        };
        load_unconnected_user(from_user_id);
        connection.send(JSON.stringify(data));
    }


    function process_chat_request(chat_request_id, from_user_id, to_user_id, action) {
        var data = {
            chat_request_id: chat_request_id,
            from_user_id: from_user_id,
            to_user_id: to_user_id,
            action: action,
            type: 'request_process_chat_request'
        };

        connection.send(JSON.stringify(data));
    }

    function load_connected_chat_user(from_user_id) {
        var data = {
            from_user_id: from_user_id,
            type: 'request_connected_chat_user'
        }

        connection.send(JSON.stringify(data));
    }
</script>
