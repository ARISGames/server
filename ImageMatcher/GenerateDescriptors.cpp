/*
 * main.cpp
 *
 *  Created on: Jun 09, 2011
 *      Author: bobbyzhu
 */

#include "opencv2/highgui/highgui.hpp"
#include "opencv2/calib3d/calib3d.hpp"
#include "opencv2/imgproc/imgproc.hpp"
#include "opencv2/features2d/features2d.hpp"

#include <iostream>
#include <fstream>
#include <sys/types.h>
#include <dirent.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <string>
#include <vector>
#include <utility>

#include "MatchParam.h"

using cv::Ptr;
using cv::FeatureDetector;
using cv::DescriptorExtractor;
using cv::DescriptorMatcher;
using cv::Mat;
using cv::FileStorage;
using cv::KeyPoint;
using cv::DMatch;
using cv::Point2f;
using std::cout;
using std::vector;
using std::string;

void ExtractDescriptor(
		const char* filename,
		Mat& descriptors,
		Mat& keypts)
{
	Ptr<FeatureDetector> detector=FeatureDetector::create(FEATURE_DETECTOR);
	Ptr<DescriptorExtractor> extractor=DescriptorExtractor::create(DESCRIPTOR_EXTRACTOR);
	printf("/*\n");

	printf("Reading image...");
	fflush(stdout);
	Mat input_img = cv::imread(filename);
	if (input_img.empty()) {
		printf("Cannot read image %s\n", filename);
		exit(1);
	} else {
		printf("Done.\n");
	}

	printf("Extracting keypoints...");
	fflush(stdout);
	vector<KeyPoint> keypoints;
	detector->detect(input_img, keypoints);
	keypts.create(keypoints.size(), 2, CV_32F);
	for (int i=0; i<keypoints.size(); i++) {
		keypts.at<float>(i, 0)=keypoints[i].pt.x;
		keypts.at<float>(i, 1)=keypoints[i].pt.y;
	}
	printf("Done.  %lu keypoints extracted.\n", keypoints.size());

	printf("Computing descriptors...");
	fflush(stdout);
	extractor->compute(input_img, keypoints, descriptors);
	printf("Done.\n");

	printf("*/\n");
}

double compareDescriptors(
		const Mat& desc1,
		const Mat& desc2,
		const Ptr<DescriptorMatcher>& matcher,
		const Mat& pts1,
		const Mat& pts2)
{
	const int K=2;
	const float DIST_THRESHOLD=0.8;
	const int MINIMUM_INLIER=50;

	vector<vector<DMatch> > matches;
	matcher->knnMatch(desc1, desc2, matches, K);


	int total_match=0;
	float distance=0;
	for (int i=0; i<matches.size(); i++) {
		if (matches[i].size()==K) {
			if (matches[i][0].distance / matches[i][1].distance < DIST_THRESHOLD) {
				total_match++;
				distance+=matches[i][0].distance;
			}
		}
	}
	printf("found %d/%d matches.  Avg distance=%f\n", total_match, matches.size(), distance/total_match);

	// Calculate a homography
	Mat srcMat(total_match, 2, CV_32F);
	Mat dstMat(total_match, 2, CV_32F);
	vector<int> srcMat_to_matches_mapping(total_match);
	int index=0;
	for (int i=0; i<matches.size(); i++) {
		if (matches[i].size()==K) {
			if (matches[i][0].distance / matches[i][1].distance < DIST_THRESHOLD) {
				// push these points to srcPts and dstPts;
				srcMat.at<float>(index, 0)=pts1.at<float>(matches[i][0].queryIdx, 0);
				srcMat.at<float>(index, 1)=pts1.at<float>(matches[i][0].queryIdx, 1);
				dstMat.at<float>(index, 0)=pts2.at<float>(matches[i][0].trainIdx, 0);
				dstMat.at<float>(index, 1)=pts2.at<float>(matches[i][0].trainIdx, 1);
				srcMat_to_matches_mapping[index]=i;

				index++;
			}
		}
	}
	assert(index==total_match);

	vector<uchar> status;
	cv::findHomography(srcMat, dstMat, status, CV_RANSAC, 5.0);

	int inlier_count=0;
	double distance_on_inliers=0;
	assert(status.size()==total_match);
	for (int i=0; i<status.size(); i++)
		if (status[i]!=0) {
			inlier_count++;
			distance_on_inliers+=matches[srcMat_to_matches_mapping[i]][0].distance;
		}
	printf("/* Inlier count=%d, inlier_distance=%lf */\n", inlier_count, distance_on_inliers / inlier_count);

	if (inlier_count < MINIMUM_INLIER)
		return 1;
	else
		return distance_on_inliers / inlier_count;
}

void printHelp(const char* progname)
{
	printf("\n\n\n");
	printf("Usage: %s [generate/match] [options]\n", progname);
	printf("\n");
	printf("For generating descriptors: \n");
	printf("\tUsage: %s generate <image metafile> <output descriptor file (xml or yml)>\n", progname);
	printf("\n");
	printf("For matching images: \n");
	printf("\tUsage: %s match <descriptor file> <input image>", progname);
	printf("\n\n\n");
}

int main(int argc, const char* argv[])
{
	if (argc<3) {
		printHelp(argv[0]);
		exit(1);
	}

	if (!strcmp(argv[1], "generate")) {
		// generating image descriptors

		if (argc!=3) {
			printHelp(argv[0]);
			exit(1);
		}

		string imagename=string(argv[2]);
		int extension=imagename.rfind('.');
		string xmlname=imagename.substr(0, extension)+".xml";

		FileStorage ofs(xmlname.c_str(), FileStorage::WRITE);
		ofs << "ver" << 1;

		Mat descriptors;
		Mat keypts;

		printf("Processing file %s.\n", imagename.c_str());
		ExtractDescriptor(imagename.c_str(), descriptors, keypts);
		ofs << "desc" << descriptors;
		ofs << "kpts" << keypts;

	} else if(!strcmp(argv[1], "match")) {
		// matching images
		if (argc!=4) {
			printHelp(argv[0]);
			exit(1);
		}

		// read input image and generate descriptors
		Mat query_desc;
		Mat query_keypts;
		ExtractDescriptor(argv[2], query_desc, query_keypts);

		// iterate through every file under one directory
		DIR* dp;
		dp=opendir(argv[3]);
		if (!dp) {
			fprintf(stderr, "Error open dir %s\n", argv[3]);
			exit(1);
		}

		dirent* dirp;

		Mat desc_in_db;
		Mat keypts_in_db;
		Ptr<DescriptorMatcher> matcher=DescriptorMatcher::create(DESCRIPTOR_MATCHER);

		vector<std::pair<double, string> > match_result;
		while (NULL != (dirp=readdir(dp))) {
			string filepath;
			if (argv[3][strlen(argv[3])-1]=='/')
				filepath=string(argv[3])+dirp->d_name;
			else
				filepath=string(argv[3])+"/"+dirp->d_name;

			if (filepath.rfind("xml")!=filepath.size()-3) continue;	// check if filename ends with xml

			// read descriptor database file
			FileStorage ifs(filepath, FileStorage::READ);
			int ver=static_cast<int>(ifs["ver"]);
			if (ver!=1) {
				fprintf(stderr, "unmatched descriptor version.  Please regenerate the descriptor file.\n");
				exit(1);
			}

			// compare descriptor with each one in the database
			ifs["desc"] >> desc_in_db;
			ifs["kpts"] >> keypts_in_db;

			double similarity=compareDescriptors(
					query_desc,
					desc_in_db,
					matcher,
					query_keypts,
					keypts_in_db);

			printf("/* %s\t%lf */\n", filepath.c_str(), similarity);
			match_result.push_back(make_pair(similarity, filepath));
		}

		// find minimum element
		int min_id=0;
		for (int i=0; i<match_result.size(); i++)
			if (match_result[i].first<match_result[min_id].first)
				min_id=i;

		printf("{\"filename\":\"%s\",\"similarity\":\"%lf\"}\n", match_result[min_id].second.c_str(), match_result[min_id].first);


	} else {
		printHelp(argv[0]);
		exit(1);
	}

	return 0;
}
