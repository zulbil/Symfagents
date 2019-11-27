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
    })
} (jQuery))