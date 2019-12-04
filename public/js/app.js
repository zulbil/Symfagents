(function ($) {
    $(document).ready(function () {
        // Handle remove event
        $('.btn-remove').on('click', function (event) {
            var agent_id    = $(this).data("agent-id"); 
            var url         = "/remove/agent/"+agent_id; 
            bootbox.confirm({
                message: "Etes vous sûr de vouloir supprimer cet agent",
                buttons: {
                    confirm: {
                        label: 'Oui',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Non',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if ( result ) {
                        $.ajax({
                            method: "GET",
                            url: url
                        }).done(function( msg ) {
                            window.location.reload(); 
                        });
                    }
                    
                }
            });
        })

        // Init DataTable
        $('#table_list_agent').DataTable({
            "language": {
                "lengthMenu": "Afficher _MENU_ agents par page",
                "zeroRecords": "Aucun agent trouvé",
                "info": " Page _PAGE_ / _PAGES_",
                "infoEmpty": "Pas d'agent trouvé",
                "infoFiltered": "(filtrer sur _MAX_ total agents)"
            }
        });

        //$('#datetimepicker1').datetimepicker();
        $('.btn-remove-task').on('click', function (event) {
            var agent_id    = $(this).data("agent-id");
            const task_id   = $(this).data("task-id");
            var url         = "/remove/task/"+task_id;
            bootbox.confirm({
                message: "Etes vous sûr de vouloir supprimer cette tâche",
                buttons: {
                    confirm: {
                        label: 'Oui',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Non',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if ( result ) {
                        $.ajax({
                            method: "GET",
                            url: url
                        }).done(function( msg ) {
                            if (msg) {
                                console.log(msg);
                            }
                            window.location.reload();
                        });
                    }

                }
            });
        })

        // $(".members-form select").on("click", function (){
        //     //console.log("Hello");
        //     var url = "/members/list";
        //     var option;
        //     $.ajax({
        //         method: "GET",
        //         url: url
        //     }).done(function( msg ) {
        //         if (msg) {
        //             var users = msg.members;
        //             $.each(users, function (index, user){
        //                 option += `
        //                     <option value="${user.id}">${user.prenom} ${user.nom}</option>
        //                 `;
        //             })
        //         }
        //     });
        //     $("select#members").html(option);
        // })

        $("select#members").select2();
        $(".btn-submit-members").on("click", function (event){
            event.preventDefault();
            var user            = $("select#members").val();
            var projet_id       = $("input[name='projet_id']").val();
            console.log(user);
            $.ajax({
                method: "POST",
                url : "/members/invite",
                data: {
                    "user_id": user,
                    "projet_id": projet_id
                }
            }).done(function (data) {
                if(data.message) {
                    toastr.success(data.message)
                }
            })
        })

        $(".btn-submit-members-task").on("click", function (event){
            event.preventDefault();
           //$('.members-form').trigger("submit");
            var user            = $("select#members").val();
            var projet_id       = $("input[name='projet_id']").val();
            var task_id         = $("input[name='task_id']").val();
            console.log(user);
            $.ajax({
                method: "POST",
                url : "/invite/task/members",
                data: {
                    "user_id": user,
                    "projet_id": projet_id,
                    "task_id" : task_id
                }
            }).done(function (data) {
                if(data) {
                    if (!data.error) {
                        toastr.success(data.message)
                    }else {
                        toastr.error(data.message)
                    }
                }
                setTimeout(function (){
                    window.location.reload();
                }, 1000)
            })
        })

        $('#remove-user-btn').on("click", function (){
            var task_id = $(this).data('task-id');
            var user_id = $(this).data('user-id');
            bootbox.confirm({
                message: "Etes vous sûr de vouloir retirer cet utilisateur",
                buttons: {
                    confirm: {
                        label: 'Oui',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Non',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if ( result ) {
                        $.ajax({
                            method: "GET",
                            url: "/remove/task/"+task_id+"/member/"+user_id,
                        }).done(function( data ) {
                            if (data) {
                                toastr.success(data.message);
                            }
                            //window.location.reload();
                            setTimeout(function (){
                                window.location.reload();
                            }, 1000)
                        });
                    }

                }
            });
        })

    })
} (jQuery))