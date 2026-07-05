class IncidentValidator:

    MAX_TITLE_LENGTH = 255
    MIN_TITLE_LENGTH = 5

    MAX_DESCRIPTION_LENGTH = 5000
    MIN_DESCRIPTION_LENGTH = 10

    MAX_LOCATION_LENGTH = 255

    MAX_COMMENT_LENGTH = 5000
    MIN_COMMENT_LENGTH = 1

    @staticmethod
    def validate_title(title: str):

        if title is None:
            raise ValueError("Title is required.")

        title = title.strip()

        if len(title) < IncidentValidator.MIN_TITLE_LENGTH:
            raise ValueError("Title is too short.")

        if len(title) > IncidentValidator.MAX_TITLE_LENGTH:
            raise ValueError("Title exceeds maximum length.")

        return title

    @staticmethod
    def validate_description(description: str):

        if description is None:
            raise ValueError("Description is required.")

        description = description.strip()

        if len(description) < IncidentValidator.MIN_DESCRIPTION_LENGTH:
            raise ValueError("Description is too short.")

        if len(description) > IncidentValidator.MAX_DESCRIPTION_LENGTH:
            raise ValueError("Description exceeds maximum length.")

        return description

    @staticmethod
    def validate_location(location: str):

        if location is None:
            raise ValueError("Location is required.")

        location = location.strip()

        if not location:
            raise ValueError("Location is required.")

        if len(location) > IncidentValidator.MAX_LOCATION_LENGTH:
            raise ValueError("Location exceeds maximum length.")

        return location

    @staticmethod
    def validate_reporter(reporter: str):

        if reporter is None:
            raise ValueError("Reporter is required.")

        reporter = reporter.strip()

        if not reporter:
            raise ValueError("Reporter is required.")

        return reporter

    @staticmethod
    def validate_author(author: str):

        if author is None:
            raise ValueError("Author is required.")

        author = author.strip()

        if not author:
            raise ValueError("Author is required.")

        return author

    @staticmethod
    def validate_comment(comment: str):

        if comment is None:
            raise ValueError("Comment is required.")

        comment = comment.strip()

        if len(comment) < IncidentValidator.MIN_COMMENT_LENGTH:
            raise ValueError("Comment cannot be empty.")

        if len(comment) > IncidentValidator.MAX_COMMENT_LENGTH:
            raise ValueError("Comment exceeds maximum length.")

        return comment
