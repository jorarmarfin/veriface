<?php

namespace Tests\Feature;

use App\Services\RekognitionService;
use Tests\TestCase;

class RekognitionServiceTest extends TestCase
{
    protected RekognitionService $rekognition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rekognition = app(RekognitionService::class);
    }

    /**
     * Test creating a collection
     *
     * @test
     */
    public function it_can_create_a_collection()
    {
        $collectionId = 'test-collection-' . time();

        $result = $this->rekognition->createCollection(
            $collectionId,
            'Test collection for unit tests'
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($collectionId, $result['collection_id']);
        $this->assertArrayHasKey('collection_arn', $result);
        $this->assertArrayHasKey('message', $result);

        // Cleanup
        $this->rekognition->deleteCollection($collectionId);
    }

    /**
     * Test listing collections
     *
     * @test
     */
    public function it_can_list_collections()
    {
        $result = $this->rekognition->listCollections(maxResults: 50);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('collections', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['collections']);
    }

    /**
     * Test describing a collection
     *
     * @test
     */
    public function it_can_describe_a_collection()
    {
        $collectionId = 'test-collection-' . time();

        // Create collection first
        $this->rekognition->createCollection($collectionId);

        $result = $this->rekognition->describeCollection($collectionId);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($collectionId, $result['collection_id']);
        $this->assertArrayHasKey('face_count', $result);
        $this->assertArrayHasKey('creation_timestamp', $result);

        // Cleanup
        $this->rekognition->deleteCollection($collectionId);
    }

    /**
     * Test deleting a collection
     *
     * @test
     */
    public function it_can_delete_a_collection()
    {
        $collectionId = 'test-collection-' . time();

        // Create collection first
        $this->rekognition->createCollection($collectionId);

        $result = $this->rekognition->deleteCollection($collectionId);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($collectionId, $result['collection_id']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test detecting faces in an image
     *
     * @test
     */
    public function it_can_detect_faces()
    {
        // Create a test image (you would use a real image file in production)
        $imagePath = storage_path('testing/sample-face.jpg');

        if (!file_exists($imagePath)) {
            $this->markTestSkipped('Sample face image not found');
        }

        $imageData = base64_encode(file_get_contents($imagePath));

        $result = $this->rekognition->detectFaces(
            imageData: $imageData,
            isBase64: true
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('faces', $result);
        $this->assertArrayHasKey('face_count', $result);
    }

    /**
     * Test indexing a face
     *
     * @test
     */
    public function it_can_index_a_face()
    {
        $collectionId = 'test-collection-' . time();
        $imagePath = storage_path('testing/sample-face.jpg');

        if (!file_exists($imagePath)) {
            $this->markTestSkipped('Sample face image not found');
        }

        // Create collection
        $this->rekognition->createCollection($collectionId);

        $imageData = base64_encode(file_get_contents($imagePath));

        $result = $this->rekognition->indexFace(
            collectionId: $collectionId,
            externalImageId: 'test-user-123',
            imageData: $imageData,
            isBase64: true
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($collectionId, $result['collection_id']);
        $this->assertEquals('test-user-123', $result['external_image_id']);
        $this->assertArrayHasKey('face_ids', $result);
        $this->assertIsArray($result['face_ids']);

        // Cleanup
        $this->rekognition->deleteCollection($collectionId);
    }

    /**
     * Test searching for faces
     *
     * @test
     */
    public function it_can_search_for_faces()
    {
        $collectionId = 'test-collection-' . time();
        $imagePath = storage_path('testing/sample-face.jpg');

        if (!file_exists($imagePath)) {
            $this->markTestSkipped('Sample face image not found');
        }

        // Create collection and index a face
        $this->rekognition->createCollection($collectionId);

        $imageData = base64_encode(file_get_contents($imagePath));

        $this->rekognition->indexFace(
            collectionId: $collectionId,
            externalImageId: 'test-user-123',
            imageData: $imageData,
            isBase64: true
        );

        // Search for the same face
        $result = $this->rekognition->searchFacesByImage(
            collectionId: $collectionId,
            imageData: $imageData,
            isBase64: true,
            faceMatchThreshold: 70
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('matches', $result);
        $this->assertArrayHasKey('match_count', $result);

        // Cleanup
        $this->rekognition->deleteCollection($collectionId);
    }

    /**
     * Test deleting faces
     *
     * @test
     */
    public function it_can_delete_faces()
    {
        $collectionId = 'test-collection-' . time();
        $imagePath = storage_path('testing/sample-face.jpg');

        if (!file_exists($imagePath)) {
            $this->markTestSkipped('Sample face image not found');
        }

        // Create collection and index a face
        $this->rekognition->createCollection($collectionId);

        $imageData = base64_encode(file_get_contents($imagePath));

        $indexResult = $this->rekognition->indexFace(
            collectionId: $collectionId,
            externalImageId: 'test-user-123',
            imageData: $imageData,
            isBase64: true
        );

        $faceIds = $indexResult['face_ids'] ?? [];

        if (!empty($faceIds)) {
            $result = $this->rekognition->deleteFaces(
                collectionId: $collectionId,
                faceIds: $faceIds
            );

            $this->assertIsArray($result);
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('deleted_faces', $result);
        }

        // Cleanup
        $this->rekognition->deleteCollection($collectionId);
    }

    /**
     * Test error handling - invalid collection
     *
     * @test
     */
    public function it_handles_errors_gracefully()
    {
        $result = $this->rekognition->describeCollection('non-existent-collection');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test getting the raw Rekognition client
     *
     * @test
     */
    public function it_can_return_the_rekognition_client()
    {
        $client = $this->rekognition->getClient();

        $this->assertNotNull($client);
        $this->assertInstanceOf(\Aws\Rekognition\RekognitionClient::class, $client);
    }
}

