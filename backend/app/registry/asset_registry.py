class AssetRegistry:

    def __init__(self):

        self.assets = {}

    def register(
        self,
        asset,
    ):

        self.assets[
            asset.asset_id
        ] = asset

    def get(
        self,
        asset_id,
    ):

        return self.assets.get(
            asset_id
        )

    def all(
        self,
    ):

        return list(
            self.assets.values()
        )
