import json
import boto3
from datetime import datetime

# Initialize AWS services
s3_client = boto3.client('s3')
dynamodb = boto3.resource('dynamodb')

# Change these values based on your project
BUCKET_NAME = "<your-bucket-name>"  # Replace with your actual S3 bucket name
UPLOADS_FOLDER = "uploads/"  # Ensure this matches your folder structure in S3
DYNAMODB_TABLE = "employee_images"  # Replace with your actual DynamoDB table name

def lambda_handler(event, context):
    try:
        print("Received event: " + json.dumps(event, indent=2))

        for record in event.get('Records', []):
            bucket_name = record['s3']['bucket']['name']
            object_key = record['s3']['object']['key']

            # Ensure the object is inside the uploads folder
            if not object_key.startswith(UPLOADS_FOLDER):
                print(f"Ignoring file {object_key}, not in {UPLOADS_FOLDER}")
                continue

            file_name = object_key[len(UPLOADS_FOLDER):]  # Extract file name

            print(f"Processing new file: {file_name} in bucket: {bucket_name}")

            # Get file metadata
            response = s3_client.head_object(Bucket=bucket_name, Key=object_key)
            file_size = response.get('ContentLength', 0)
            content_type = response.get('ContentType', 'unknown')
            last_modified = response.get('LastModified')

            # Convert last_modified to string format (ISO 8601)
            upload_time = last_modified.strftime('%Y-%m-%dT%H:%M:%SZ') if last_modified else "Unknown"

            # Store metadata in DynamoDB (without setting image_id explicitly)
            table = dynamodb.Table(DYNAMODB_TABLE)
            table.put_item(Item={
                'file_name': file_name,
                'bucket_name': bucket_name,
                'file_size': int(file_size),
                'content_type': content_type,
                'upload_time': upload_time
            })

            print(f"Metadata for {file_name} stored in DynamoDB")

        return {
            'statusCode': 200,
            'body': json.dumps('S3 upload event processed successfully!')
        }

    except Exception as e:
        print(f"Error processing S3 event: {str(e)}")
        return {
            'statusCode': 500,
            'body': json.dumps(f"Error: {str(e)}")
        }
