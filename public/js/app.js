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

        $("select#members").select2();
        // Ajout de l'utilisateur dans un projet
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
                if(data) {
                    if (!data.error)
                        toastr.success(data.message)
                    else
                        toastr.error(data.message)
                }

                setTimeout(function (){
                    window.location.reload();
                }, 1000)
            })
        })
        // Ajout de l'utilisateur dans une tâche
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

        // Suppression de l'utilisateur sur une tâche
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

        $('#remove-member').on ("click", function () {
            var projet_id = $(this).data('projet-id');
            var user_id = $(this).data('member-id');
            bootbox.confirm({
                message: "Etes vous sûr de vouloir retirer cet utilisateur de ce projet",
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
                            url: "/remove/projet/"+projet_id+"/member/"+user_id,
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

        $('.btn-complete').on("click", function (event){
            var task_id = $(this).data('task-id');
            bootbox.confirm({
                message: "Etes vous sûr de vouloir finir cette tâche",
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
                            url: "/task/complete/"+task_id,
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

        // Init DataTable
        if ( $('#kt_datatable').length ) {
            var datatable = $('#kt_datatable').KTDatatable({
                // datasource definition
                data: {
                    type: 'remote',
                    source: {
                        read: {
                            url: '/user/gettasks',
                            map: function(raw) {
                                // sample data mapping
                                var dataSet = raw;
                                if (typeof raw.data !== 'undefined') {
                                    dataSet = raw.data;
                                }
                                return dataSet;
                            },
                        },
                    },
                    pageSize: 10,
                    serverPaging: true,
                    serverFiltering: true,
                    serverSorting: true,
                },

                // layout definition
                layout: {
                    scroll: false,
                    footer: false,
                },

                // column sorting
                sortable: true,

                pagination: true,

                search: {
                    input: $('#generalSearch'),
                },

                // columns definition
                columns: [
                    {
                        field: 'id',
                        title: '#',
                        sortable: 'asc',
                        width: 40,
                        type: 'number',
                        selector: false,
                        textAlign: 'center',
                    }, {
                        field: 'nom',
                        title: 'Nom de la tâche',
                    }, {
                        field: 'description',
                        title: 'Description',
                        template: function(row, index, datatable) {
                            return row.first_name + ' ' + row.last_name;
                        },
                    }, {
                        field: 'date_debut',
                        width: 150,
                        title: 'Debut du projet',
                        type: "datetime",
                        format: 'MM/DD/YYYY'
                    }, {
                        field: 'date_fin',
                        title: 'Fin de la tâche',
                        type: 'datetime',
                        format: 'MM/DD/YYYY'
                    }, {
                        field: 'statut',
                        title: 'Statut',
                        // callback function support for column rendering
                        template: function(row) {
                            var status = {
                                0: {'title': 'En cours', 'class': 'kt-badge--brand'},
                                1: {'title': 'Terminé', 'class': ' kt-badge--metal'}
                            };
                            return '<span class="kt-badge ' + status[row.status].class + ' kt-badge--inline kt-badge--pill">' + status[row.status].title + '</span>';
                        },
                    }, {
                        field: 'priorite',
                        title: 'Priorité',
                        // callback function support for column rendering
                        template: function(row) {
                            var status = {
                                0: {'title': 'Pending', 'class': 'kt-badge--brand'},
                                1: {'title': 'Delivered', 'class': ' kt-badge--metal'},
                                2: {'title': 'Canceled', 'class': ' kt-badge--primary'},
                            };
                            return '<span class="kt-badge ' + status[row.status].class + ' kt-badge--inline kt-badge--pill">' + status[row.status].title + '</span>';
                        },
                    },  {
                        field: 'Actions',
                        title: 'Actions',
                        sortable: false,
                        width: 130,
                        overflow: 'visible',
                        textAlign: 'center',
                        template: function(row, index, datatable) {
                            var dropup = (datatable.getPageSize() - index) <= 4 ? 'dropup' : '';
                            return '<div class="dropdown ' + dropup + '">\
                        <a href="#" class="btn btn-hover-brand btn-icon btn-pill" data-toggle="dropdown">\
                            <i class="la la-ellipsis-h"></i>\
                        </a>\
                        <div class="dropdown-menu dropdown-menu-right">\
                            <a class="dropdown-item" href="#"><i class="la la-edit"></i> Edit Details</a>\
                            <a class="dropdown-item" href="#"><i class="la la-leaf"></i> Update Status</a>\
                            <a class="dropdown-item" href="#"><i class="la la-print"></i> Generate Report</a>\
                        </div>\
                    </div>\
                    <a href="#" class="btn btn-hover-brand btn-icon btn-pill" title="Edit details">\
                        <i class="la la-edit"></i>\
                    </a>\
                    <a href="#" class="btn btn-hover-danger btn-icon btn-pill" title="Delete">\
                        <i class="la la-trash"></i>\
                    </a>';
                        },
                    }],

            });
        }
    })
} (jQuery))