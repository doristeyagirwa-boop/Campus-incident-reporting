POLICY_REGISTRY = {

    "SECURITY": {

        "requires_manager":
            True,

        "requires_audit":
            True,

        "requires_attachment":
            True,
    },

    "NETWORK": {

        "requires_manager":
            False,

        "requires_audit":
            True,

        "requires_attachment":
            False,
    },

    "SAFETY": {

        "requires_manager":
            True,

        "requires_audit":
            True,

        "requires_attachment":
            True,
    },
}
